<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Tickets récents
        |--------------------------------------------------------------------------
        | On alimente le tableau de bord avec les derniers tickets visibles
        | pour l'utilisateur connecté.
        |
        | Règles :
        | - client : uniquement ses tickets
        | - agent  : ses tickets assignés + les tickets non assignés
        | - admin  : tous les tickets
        */
        $recentQuery = Ticket::with(['user', 'agent'])->latest();

        if ($user->isClient()) {
            $recentQuery->forClient($user);
        } elseif ($user->role === User::ROLE_AGENT) {
            $recentQuery->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhereNull('assigned_to');
            });
        }

        $recentTickets = $recentQuery->take(5)->get();

        /*
        |--------------------------------------------------------------------------
        | Variables initialisées par défaut
        |--------------------------------------------------------------------------
        | Cela évite les erreurs dans la vue si certains blocs ne sont pas utilisés.
        */
        $byPriority = collect();
        $byStatus   = collect();
        $agentLoad  = collect();

        if ($user->isAgent()) {
            /*
            |--------------------------------------------------------------------------
            | Dashboard interne (agent/admin)
            |--------------------------------------------------------------------------
            | Ces statistiques servent à piloter l'activité support.
            */
            $stats = [
                'open_total' => Ticket::where('status', Ticket::STATUS_OPEN)->count(),

                'in_progress' => Ticket::where('status', Ticket::STATUS_IN_PROGRESS)->count(),

                'my_assigned' => Ticket::where('assigned_to', $user->id)
                    ->whereNotIn('status', Ticket::RESOLVED_STATUSES)
                    ->count(),

                /*
                | On compte les tickets résolus aujourd'hui.
                | Le projet stocke la date dans resolved_at.
                */
                'resolved_today' => Ticket::whereIn('status', Ticket::RESOLVED_STATUSES)
                    ->whereDate('resolved_at', today())
                    ->count(),

                'overdue' => Ticket::overdue()->count(),

                /*
                | avg() retourne parfois null s'il n'existe encore aucune note.
                | On force alors 0 pour éviter un round(null, 1) ambigu.
                */
                'avg_rating' => round(
                    Ticket::whereNotNull('satisfaction_rating')->avg('satisfaction_rating') ?? 0,
                    1
                ),
            ];

            /*
            |--------------------------------------------------------------------------
            | Répartition par priorité
            |--------------------------------------------------------------------------
            | On ne garde ici que les tickets encore actifs pour obtenir
            | une vue opérationnelle plus utile.
            */
            $byPriority = Ticket::selectRaw('priority, count(*) as count')
                ->whereNotIn('status', Ticket::RESOLVED_STATUSES)
                ->groupBy('priority')
                ->pluck('count', 'priority');

            /*
            |--------------------------------------------------------------------------
            | Répartition par statut
            |--------------------------------------------------------------------------
            | Vue d'ensemble de tous les tickets par étape de traitement.
            */
            $byStatus = Ticket::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            /*
            |--------------------------------------------------------------------------
            | Charge des agents
            |--------------------------------------------------------------------------
            | On calcule le nombre de tickets actifs assignés à chaque agent/admin.
            | Cela suppose que User possède bien la relation assignedTickets().
            */
            $agentLoad = User::agents()
                ->withCount(['assignedTickets as open_count' => function ($q) {
                    $q->whereNotIn('status', Ticket::RESOLVED_STATUSES);
                }])
                ->orderByDesc('open_count')
                ->take(5)
                ->get();
        } else {
            /*
            |--------------------------------------------------------------------------
            | Dashboard client
            |--------------------------------------------------------------------------
            | Vue simplifiée centrée sur le suivi des tickets personnels.
            */
            $base = Ticket::query()->forClient($user);

            $stats = [
                'total' => (clone $base)->count(),

                'open' => (clone $base)
                    ->where('status', Ticket::STATUS_OPEN)
                    ->count(),

                'resolved' => (clone $base)
                    ->whereIn('status', Ticket::RESOLVED_STATUSES)
                    ->count(),

                'pending' => (clone $base)
                    ->whereIn('status', [
                        Ticket::STATUS_IN_PROGRESS,
                        Ticket::STATUS_WAITING_CLIENT,
                    ])
                    ->count(),
            ];
        }

        return view('dashboard.index', compact(
            'recentTickets',
            'stats',
            'byPriority',
            'byStatus',
            'agentLoad'
        ));
    }
}