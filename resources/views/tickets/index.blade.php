@extends('layouts.app')

@section('title', 'Tickets')
@section('page-title', 'Tickets de support')

@section('content')
@php
    /*
    |--------------------------------------------------------------------------
    | Préparation des données de vue
    |--------------------------------------------------------------------------
    | On prépare ici les options de filtres et quelques variables de présentation
    | pour éviter de répéter la logique dans le template.
    */
    $currentUser = Auth::user();

    $statusOptions = [
        \App\Models\Ticket::STATUS_OPEN => 'Ouvert',
        \App\Models\Ticket::STATUS_IN_PROGRESS => 'En cours',
        \App\Models\Ticket::STATUS_WAITING_CLIENT => 'Attente client',
        \App\Models\Ticket::STATUS_RESOLVED => 'Résolu',
        \App\Models\Ticket::STATUS_CLOSED => 'Fermé',
    ];

    $priorityOptions = [
        \App\Models\Ticket::PRIORITY_CRITICAL => 'Critique',
        \App\Models\Ticket::PRIORITY_HIGH => 'Élevée',
        \App\Models\Ticket::PRIORITY_MEDIUM => 'Moyenne',
        \App\Models\Ticket::PRIORITY_LOW => 'Faible',
    ];

    $categoryOptions = [
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
    | Détection des filtres actifs
    |--------------------------------------------------------------------------
    | Sert à afficher proprement le bouton de réinitialisation.
    */
    $hasActiveFilters = request()->anyFilled(['search', 'status', 'priority', 'category']);

    /*
    |--------------------------------------------------------------------------
    | Détermination du colspan du tableau vide
    |--------------------------------------------------------------------------
    | Le nombre de colonnes change selon le rôle.
    */
    $emptyColspan = $currentUser->isAgent() ? 7 : 5;

    /*
    |--------------------------------------------------------------------------
    | Cartes KPI
    |--------------------------------------------------------------------------
    | On prépare les données ici pour garder la boucle d'affichage très lisible.
    */
    $kpiList = [
        ['Total',     $stats['total'],       'bg-slate-50',  'text-slate-900',  'text-slate-500'],
        ['Ouverts',   $stats['open'],        'bg-blue-50',   'text-blue-900',   'text-blue-600'],
        ['En cours',  $stats['in_progress'], 'bg-amber-50',  'text-amber-900',  'text-amber-600'],
        ['Résolus',   $stats['resolved'],    'bg-green-50',  'text-green-900',  'text-green-600'],
        ['Critiques', $stats['critical'],    'bg-red-50',    'text-red-900',    'text-red-600'],
        ['En retard', $stats['overdue'],     'bg-orange-50', 'text-orange-900', 'text-orange-600'],
    ];
@endphp

<div class="space-y-5">

    {{-- =========================================================================
         INDICATEURS KPI
         ========================================================================= --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        @foreach($kpiList as [$label, $value, $backgroundClass, $valueClass, $labelClass])
            <div class="{{ $backgroundClass }} rounded-xl p-4 border border-slate-200">
                <p class="text-2xl font-bold {{ $valueClass }}">{{ $value }}</p>
                <p class="text-xs font-medium {{ $labelClass }} mt-1">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    <div class="card">

        {{-- =========================================================================
             BARRE D’OUTILS : FILTRES + ACTION PRINCIPALE
             ========================================================================= --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 p-4 border-b border-slate-200">
            <form method="GET" action="{{ route('tickets.index') }}" class="flex flex-wrap items-center gap-2 flex-1">

                {{-- Recherche texte libre --}}
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Rechercher…"
                    class="form-input w-48"
                >

                {{-- Filtre statut --}}
                <select name="status" class="form-input w-40">
                    <option value="">Tous statuts</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                {{-- Filtre priorité --}}
                <select name="priority" class="form-input w-36">
                    <option value="">Toutes priorités</option>
                    @foreach($priorityOptions as $value => $label)
                        <option value="{{ $value }}" {{ request('priority') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                {{-- Filtre catégorie --}}
                <select name="category" class="form-input w-40">
                    <option value="">Toutes catégories</option>
                    @foreach($categoryOptions as $value => $label)
                        <option value="{{ $value }}" {{ request('category') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="btn-primary">Filtrer</button>

                @if($hasActiveFilters)
                    {{-- Réinitialisation propre de tous les filtres --}}
                    <a href="{{ route('tickets.index') }}" class="btn-secondary">Réinitialiser</a>
                @endif
            </form>

            {{-- Action principale de la page --}}
            <a href="{{ route('tickets.create') }}" class="btn-primary whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Nouveau ticket</span>
            </a>
        </div>

        {{-- =========================================================================
             TABLEAU DES TICKETS
             ========================================================================= --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left bg-slate-50/50">
                        <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Référence
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Sujet
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Priorité
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Statut
                        </th>

                        @if($currentUser->isAgent())
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Demandeur
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Assigné à
                            </th>
                        @endif

                        <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Mis à jour
                        </th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($tickets as $ticket)
                        <tr class="hover:bg-slate-50 transition-colors {{ $ticket->isOverdue() ? 'bg-red-50/40' : '' }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    @if($ticket->isOverdue())
                                        {{-- Indicateur rapide de ticket en retard --}}
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span>
                                    @endif

                                    <span class="font-mono text-xs text-slate-500 font-medium">
                                        {{ $ticket->reference }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-4 py-3 max-w-xs">
                                <p class="font-medium text-slate-900 truncate">{{ $ticket->title }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">{{ $ticket->categoryLabel() }}</p>
                            </td>

                            <td class="px-4 py-3">
                                <span class="priority-{{ $ticket->priority }}">
                                    {{ $ticket->priorityLabel() }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <span class="status-{{ $ticket->status }}">
                                    {{ $ticket->statusLabel() }}
                                </span>
                            </td>

                            @if($currentUser->isAgent())
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-xs font-medium text-slate-600">
                                            {{-- On utilise l’accessor initials du modèle User --}}
                                            {{ $ticket->user->initials }}
                                        </div>
                                        <span class="text-slate-700 text-xs truncate max-w-[90px]">
                                            {{ $ticket->user->name }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    @if($ticket->agent)
                                        <div class="flex items-center gap-1.5">
                                            <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-medium">
                                                {{ $ticket->agent->initials }}
                                            </div>
                                            <span class="text-xs text-slate-700 truncate max-w-[110px]">
                                                {{ $ticket->agent->name }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-slate-400 text-xs italic">Non assigné</span>
                                    @endif
                                </td>
                            @endif

                            <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                                {{ $ticket->updated_at->diffForHumans() }}
                            </td>

                            <td class="px-4 py-3">
                                <a
                                    href="{{ route('tickets.show', $ticket) }}"
                                    class="text-blue-600 hover:text-blue-800 text-xs font-medium whitespace-nowrap"
                                >
                                    Voir
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $emptyColspan }}" class="px-4 py-16 text-center">
                                <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>

                                <p class="text-sm text-slate-400 mb-3">Aucun ticket trouvé</p>

                                <a href="{{ route('tickets.create') }}" class="btn-primary text-xs">
                                    Créer un ticket
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- =========================================================================
             PAGINATION
             ========================================================================= --}}
        @if($tickets->hasPages())
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
@endsection