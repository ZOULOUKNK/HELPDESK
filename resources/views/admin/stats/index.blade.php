@extends('layouts.app')

@section('title', 'Statistiques')
@section('page-title', 'Pilotage support')

@section('content')
@php
    /*
    |--------------------------------------------------------------------------
    | Préparation des données de vue
    |--------------------------------------------------------------------------
    | On prépare ici les cartes KPI, les labels métier et les bornes
    | d'affichage pour éviter de charger la vue principale.
    */
    $kpiCards = [
        [
            'label' => 'Tickets total',
            'value' => $kpis['total_tickets'],
            'bg' => 'bg-slate-50',
            'text' => 'text-slate-900',
            'sub' => 'text-slate-500',
        ],
        [
            'label' => 'Ouverts',
            'value' => $kpis['open_tickets'],
            'bg' => 'bg-indigo-50',
            'text' => 'text-indigo-900',
            'sub' => 'text-indigo-600',
        ],
        [
            'label' => 'En cours',
            'value' => $kpis['in_progress_tickets'],
            'bg' => 'bg-amber-50',
            'text' => 'text-amber-900',
            'sub' => 'text-amber-600',
        ],
        [
            'label' => 'Résolus',
            'value' => $kpis['resolved_tickets'],
            'bg' => 'bg-green-50',
            'text' => 'text-green-900',
            'sub' => 'text-green-600',
        ],
        [
            'label' => 'En retard',
            'value' => $kpis['overdue_tickets'],
            'bg' => 'bg-red-50',
            'text' => 'text-red-900',
            'sub' => 'text-red-600',
        ],
        [
            'label' => 'SLA respecté',
            'value' => $kpis['sla_rate'] . '%',
            'bg' => 'bg-blue-50',
            'text' => 'text-blue-900',
            'sub' => 'text-blue-600',
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | Libellés catégorie
    |--------------------------------------------------------------------------
    | On garde ici une correspondance lisible pour la direction.
    */
    $categoryLabels = [
        \App\Models\Ticket::CATEGORY_BUG => 'Bug',
        \App\Models\Ticket::CATEGORY_FEATURE_REQUEST => 'Fonctionnalité',
        \App\Models\Ticket::CATEGORY_ACCESS => 'Accès',
        \App\Models\Ticket::CATEGORY_HARDWARE => 'Matériel',
        \App\Models\Ticket::CATEGORY_SOFTWARE => 'Logiciel',
        \App\Models\Ticket::CATEGORY_NETWORK => 'Réseau',
        \App\Models\Ticket::CATEGORY_OTHER => 'Autre',
    ];

    /*
    |--------------------------------------------------------------------------
    | Bornes d'affichage
    |--------------------------------------------------------------------------
    | On force un minimum à 1 pour éviter toute division par zéro.
    */
    $maxCategoryValue = max($byCategory->max() ?: 1, 1);
    $maxDepartmentValue = max($byDepartment->max() ?: 1, 1);
    $maxTrendValue = max(
        $createdTrend->max('count') ?: 1,
        $resolvedTrend->max('count') ?: 1,
        1
    );

    /*
    |--------------------------------------------------------------------------
    | Résumé direction
    |--------------------------------------------------------------------------
    | Bloc synthétique qui aide à lire rapidement la situation globale.
    */
    $managementSummary = [
        'backlog' => $kpis['open_tickets'] + $kpis['in_progress_tickets'],
        'quality' => $kpis['avg_rating'] !== null ? $kpis['avg_rating'] . '/5' : '—',
        'speed' => $kpis['avg_resolution_time'] !== null ? $kpis['avg_resolution_time'] . ' h' : '—',
        'first_response' => $kpis['avg_first_response_time'] !== null ? $kpis['avg_first_response_time'] . ' h' : '—',
    ];
@endphp

<div class="space-y-6">

    {{-- =========================================================================
         EN-TÊTE DE SYNTHÈSE
         ========================================================================= --}}
    <div class="card p-5">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Vue direction</h2>
                <p class="text-sm text-slate-500 mt-1">
                    Suivi global de l’activité support, de la charge et de la qualité de traitement.
                </p>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 min-w-0">
                <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3">
                    <p class="text-xs text-slate-500">Backlog actif</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ $managementSummary['backlog'] }}</p>
                </div>

                <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3">
                    <p class="text-xs text-slate-500">Résolution moy.</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ $managementSummary['speed'] }}</p>
                </div>

                <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3">
                    <p class="text-xs text-slate-500">1ère réponse</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ $managementSummary['first_response'] }}</p>
                </div>

                <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3">
                    <p class="text-xs text-slate-500">Satisfaction</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ $managementSummary['quality'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================================
         KPI PRINCIPAUX
         ========================================================================= --}}
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
        @foreach($kpiCards as $card)
            <div class="{{ $card['bg'] }} rounded-xl p-4 border border-slate-200">
                <p class="text-2xl font-bold {{ $card['text'] }}">{{ $card['value'] }}</p>
                <p class="text-xs font-medium {{ $card['sub'] }} mt-1">{{ $card['label'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- =========================================================================
         KPI QUALITÉ
         ========================================================================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="card p-5">
            <p class="text-sm text-slate-500">Temps moyen de résolution</p>
            <p class="text-2xl font-bold text-slate-900 mt-2">
                {{ $kpis['avg_resolution_time'] !== null ? $kpis['avg_resolution_time'] . ' h' : '—' }}
            </p>
            <p class="text-xs text-slate-400 mt-2">
                Temps moyen entre création et résolution effective du ticket.
            </p>
        </div>

        <div class="card p-5">
            <p class="text-sm text-slate-500">Temps moyen de première réponse</p>
            <p class="text-2xl font-bold text-slate-900 mt-2">
                {{ $kpis['avg_first_response_time'] !== null ? $kpis['avg_first_response_time'] . ' h' : '—' }}
            </p>
            <p class="text-xs text-slate-400 mt-2">
                Mesure de réactivité perçue par les utilisateurs.
            </p>
        </div>

        <div class="card p-5">
            <p class="text-sm text-slate-500">Satisfaction moyenne</p>
            <p class="text-2xl font-bold text-slate-900 mt-2">
                {{ $kpis['avg_rating'] !== null ? $kpis['avg_rating'] . '/5' : '—' }}
            </p>
            <p class="text-xs text-slate-400 mt-2">
                Moyenne des évaluations laissées sur les tickets terminés.
            </p>
        </div>
    </div>

    {{-- =========================================================================
         RÉPARTITIONS
         ========================================================================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Répartition par catégorie --}}
        <div class="card p-5">
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-slate-900">Répartition par catégorie</h3>
                <p class="text-xs text-slate-400 mt-1">
                    Types de demandes les plus représentés dans l’activité support.
                </p>
            </div>

            @foreach($categoryLabels as $category => $label)
                @php
                    $count = $byCategory[$category] ?? 0;
                    $width = round(($count / $maxCategoryValue) * 100);
                @endphp

                <div class="flex items-center gap-3 mb-3 last:mb-0">
                    <span class="text-xs text-slate-600 w-28 flex-shrink-0">{{ $label }}</span>

                    <div class="flex-1 bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-slate-600 h-full rounded-full" style="width: {{ $width }}%"></div>
                    </div>

                    <span class="text-xs font-bold text-slate-700 w-6 text-right">{{ $count }}</span>
                </div>
            @endforeach
        </div>

        {{-- Répartition par département --}}
        <div class="card p-5">
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-slate-900">Répartition par département</h3>
                <p class="text-xs text-slate-400 mt-1">
                    Équipes internes les plus consommatrices de support.
                </p>
            </div>

            @forelse($byDepartment as $department => $count)
                @php
                    $width = round(($count / $maxDepartmentValue) * 100);
                @endphp

                <div class="flex items-center gap-3 mb-3 last:mb-0">
                    <span class="text-xs text-slate-600 w-32 flex-shrink-0 truncate">{{ $department }}</span>

                    <div class="flex-1 bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-indigo-500 h-full rounded-full" style="width: {{ $width }}%"></div>
                    </div>

                    <span class="text-xs font-bold text-slate-700 w-6 text-right">{{ $count }}</span>
                </div>
            @empty
                <p class="text-sm text-slate-400">Aucune donnée disponible.</p>
            @endforelse
        </div>
    </div>

    {{-- =========================================================================
         CHARGE ET PERFORMANCE DES AGENTS
         ========================================================================= --}}
    <div class="card p-5">
        <div class="mb-4">
            <h3 class="text-sm font-semibold text-slate-900">Charge et performance des agents</h3>
            <p class="text-xs text-slate-400 mt-1">
                Suivi de la capacité de traitement et du volume pris en charge par agent.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left">
                        <th class="py-3 pr-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Agent</th>
                        <th class="py-3 pr-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Rôle</th>
                        <th class="py-3 pr-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Tickets actifs</th>
                        <th class="py-3 pr-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Tickets résolus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($agentLoad as $agent)
                        <tr>
                            <td class="py-3 pr-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center text-xs font-bold">
                                        {{ $agent->initials }}
                                    </div>
                                    <span class="font-medium text-slate-900">{{ $agent->name }}</span>
                                </div>
                            </td>
                            <td class="py-3 pr-4 text-slate-600">{{ ucfirst($agent->role) }}</td>
                            <td class="py-3 pr-4 font-semibold text-slate-900">{{ $agent->open_count }}</td>
                            <td class="py-3 pr-4 font-semibold text-slate-900">{{ $agent->resolved_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-400">Aucun agent disponible.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- =========================================================================
         TENDANCES SUR 30 JOURS
         ========================================================================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Tickets créés --}}
        <div class="card p-5">
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-slate-900">Tickets créés sur 30 jours</h3>
                <p class="text-xs text-slate-400 mt-1">
                    Évolution quotidienne du volume entrant.
                </p>
            </div>

            <div class="space-y-2">
                @foreach($createdTrend as $point)
                    @php
                        $width = round(($point['count'] / $maxTrendValue) * 100);
                    @endphp

                    <div class="flex items-center gap-3">
                        <span class="text-xs text-slate-500 w-10 flex-shrink-0">{{ $point['label'] }}</span>
                        <div class="flex-1 bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="bg-slate-700 h-full rounded-full" style="width: {{ $width }}%"></div>
                        </div>
                        <span class="text-xs text-slate-700 w-6 text-right">{{ $point['count'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Tickets résolus --}}
        <div class="card p-5">
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-slate-900">Tickets résolus sur 30 jours</h3>
                <p class="text-xs text-slate-400 mt-1">
                    Évolution quotidienne du volume traité.
                </p>
            </div>

            <div class="space-y-2">
                @foreach($resolvedTrend as $point)
                    @php
                        $width = round(($point['count'] / $maxTrendValue) * 100);
                    @endphp

                    <div class="flex items-center gap-3">
                        <span class="text-xs text-slate-500 w-10 flex-shrink-0">{{ $point['label'] }}</span>
                        <div class="flex-1 bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="bg-green-600 h-full rounded-full" style="width: {{ $width }}%"></div>
                        </div>
                        <span class="text-xs text-slate-700 w-6 text-right">{{ $point['count'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection