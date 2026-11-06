<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | Driver courant
        |--------------------------------------------------------------------------
        | On adapte certains calculs SQL selon le moteur utilisé :
        | - sqlite en local
        | - mysql en environnement classique WAMP/phpMyAdmin
        */
        $driver = DB::connection()->getDriverName();

        /*
        |--------------------------------------------------------------------------
        | Indicateurs globaux
        |--------------------------------------------------------------------------
        | Vue consolidée pour la direction.
        */
        $totalTickets = Ticket::count();

        $openTickets = Ticket::where('status', Ticket::STATUS_OPEN)->count();

        $inProgressTickets = Ticket::where('status', Ticket::STATUS_IN_PROGRESS)->count();

        $resolvedTickets = Ticket::whereIn('status', Ticket::RESOLVED_STATUSES)->count();

        $overdueTickets = Ticket::overdue()->count();

        /*
        |--------------------------------------------------------------------------
        | SLA respecté
        |--------------------------------------------------------------------------
        | Un ticket est considéré conforme si resolved_at <= due_at.
        */
        $resolvedWithDueDateCount = Ticket::query()
            ->whereNotNull('resolved_at')
            ->whereNotNull('due_at')
            ->count();

        $slaCompliantCount = Ticket::query()
            ->whereNotNull('resolved_at')
            ->whereNotNull('due_at')
            ->whereColumn('resolved_at', '<=', 'due_at')
            ->count();

        $slaRate = $resolvedWithDueDateCount > 0
            ? round(($slaCompliantCount / $resolvedWithDueDateCount) * 100, 1)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Temps moyens
        |--------------------------------------------------------------------------
        | TIMESTAMPDIFF n'existe pas en SQLite.
        | On calcule donc :
        | - MySQL  : TIMESTAMPDIFF(HOUR, ...)
        | - SQLite : (julianday(fin) - julianday(début)) * 24
        */
        if ($driver === 'sqlite') {
            $avgResolutionTime = Ticket::query()
                ->whereNotNull('resolved_at')
                ->selectRaw('AVG((julianday(resolved_at) - julianday(created_at)) * 24) as avg_hours')
                ->value('avg_hours');

            $avgFirstResponseTime = Ticket::query()
                ->whereNotNull('first_response_at')
                ->selectRaw('AVG((julianday(first_response_at) - julianday(created_at)) * 24) as avg_hours')
                ->value('avg_hours');
        } else {
            $avgResolutionTime = Ticket::query()
                ->whereNotNull('resolved_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
                ->value('avg_hours');

            $avgFirstResponseTime = Ticket::query()
                ->whereNotNull('first_response_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, first_response_at)) as avg_hours')
                ->value('avg_hours');
        }

        $avgRating = Ticket::query()
            ->whereNotNull('satisfaction_rating')
            ->avg('satisfaction_rating');

        /*
        |--------------------------------------------------------------------------
        | Répartition par catégorie
        |--------------------------------------------------------------------------
        */
        $byCategory = Ticket::query()
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category');

        /*
        |--------------------------------------------------------------------------
        | Répartition par département
        |--------------------------------------------------------------------------
        | On remplace les valeurs nulles par un libellé lisible.
        */
        if ($driver === 'sqlite') {
            $byDepartment = Ticket::query()
                ->selectRaw("COALESCE(department, 'Non renseigné') as department, COUNT(*) as count")
                ->groupBy('department')
                ->orderByDesc('count')
                ->pluck('count', 'department');
        } else {
            $byDepartment = Ticket::query()
                ->selectRaw('COALESCE(department, "Non renseigné") as department, COUNT(*) as count')
                ->groupBy('department')
                ->orderByDesc('count')
                ->pluck('count', 'department');
        }

        /*
        |--------------------------------------------------------------------------
        | Charge agent
        |--------------------------------------------------------------------------
        | Nombre de tickets actifs et résolus par agent/admin.
        */
        $agentLoad = User::query()
            ->whereIn('role', [User::ROLE_AGENT, User::ROLE_ADMIN])
            ->withCount([
                'assignedTickets as open_count' => function ($query) {
                    $query->whereNotIn('status', Ticket::RESOLVED_STATUSES);
                },
                'assignedTickets as resolved_count' => function ($query) {
                    $query->whereIn('status', Ticket::RESOLVED_STATUSES);
                },
            ])
            ->orderByDesc('open_count')
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Tendances sur 30 jours
        |--------------------------------------------------------------------------
        | Tickets créés / résolus par jour.
        */
        $createdTrendRaw = Ticket::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $resolvedTrendRaw = Ticket::query()
            ->selectRaw('DATE(resolved_at) as date, COUNT(*) as count')
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        /*
        |--------------------------------------------------------------------------
        | Séries complètes sur 30 jours
        |--------------------------------------------------------------------------
        | On remplit les jours absents avec zéro.
        */
        $createdTrend = collect();
        $resolvedTrend = collect();

        foreach (range(29, 0) as $daysAgo) {
            $date = now()->subDays($daysAgo)->toDateString();

            $createdTrend->push([
                'date' => $date,
                'label' => now()->subDays($daysAgo)->format('d/m'),
                'count' => (int) ($createdTrendRaw[$date]->count ?? 0),
            ]);

            $resolvedTrend->push([
                'date' => $date,
                'label' => now()->subDays($daysAgo)->format('d/m'),
                'count' => (int) ($resolvedTrendRaw[$date]->count ?? 0),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Données packées pour la vue
        |--------------------------------------------------------------------------
        */
        $kpis = [
            'total_tickets' => $totalTickets,
            'open_tickets' => $openTickets,
            'in_progress_tickets' => $inProgressTickets,
            'resolved_tickets' => $resolvedTickets,
            'overdue_tickets' => $overdueTickets,
            'sla_rate' => $slaRate,
            'avg_resolution_time' => $avgResolutionTime !== null ? round((float) $avgResolutionTime, 1) : null,
            'avg_first_response_time' => $avgFirstResponseTime !== null ? round((float) $avgFirstResponseTime, 1) : null,
            'avg_rating' => $avgRating !== null ? round((float) $avgRating, 1) : null,
        ];

        return view('admin.stats.index', compact(
            'kpis',
            'byCategory',
            'byDepartment',
            'agentLoad',
            'createdTrend',
            'resolvedTrend'
        ));
    }
}