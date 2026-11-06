@extends('layouts.app')

@section('title', 'Gestion des utilisateurs')
@section('page-title', 'Gestion des utilisateurs')

@section('content')
@php
    /*
    |--------------------------------------------------------------------------
    | Préparation des données de vue
    |--------------------------------------------------------------------------
    | On prépare les éléments d'affichage pour éviter les répétitions
    | et rendre le template plus lisible.
    */
    $currentUser = auth()->user();

    $roleOptions = [
        \App\Models\User::ROLE_ADMIN => 'Admin',
        \App\Models\User::ROLE_AGENT => 'Agent',
        \App\Models\User::ROLE_CLIENT => 'Client',
    ];

    $statusOptions = [
        'active' => 'Actif',
        'inactive' => 'Inactif',
    ];

    $kpiList = [
        ['Total',    $stats['total'],    'bg-slate-50',  'text-slate-900',  'text-slate-500'],
        ['Admins',   $stats['admins'],   'bg-purple-50', 'text-purple-900', 'text-purple-600'],
        ['Agents',   $stats['agents'],   'bg-blue-50',   'text-blue-900',   'text-blue-600'],
        ['Clients',  $stats['clients'],  'bg-green-50',  'text-green-900',  'text-green-600'],
        ['Inactifs', $stats['inactive'], 'bg-red-50',    'text-red-900',    'text-red-600'],
    ];

    /*
    |--------------------------------------------------------------------------
    | Détection des filtres actifs
    |--------------------------------------------------------------------------
    | Sert à piloter l'affichage du bouton de réinitialisation.
    */
    $hasActiveFilters = request()->anyFilled(['search', 'role', 'status']);

    /*
    |--------------------------------------------------------------------------
    | Helpers d'affichage des rôles
    |--------------------------------------------------------------------------
    | Chaque rôle possède ici un libellé et un style cohérent.
    */
    $roleBadgeMap = [
        \App\Models\User::ROLE_ADMIN => [
            'label' => 'Administrateur',
            'badge' => 'badge bg-purple-100 text-purple-800',
            'avatar' => 'bg-purple-100 text-purple-700',
        ],
        \App\Models\User::ROLE_AGENT => [
            'label' => 'Agent',
            'badge' => 'badge bg-blue-100 text-blue-800',
            'avatar' => 'bg-blue-100 text-blue-700',
        ],
        \App\Models\User::ROLE_CLIENT => [
            'label' => 'Client',
            'badge' => 'badge bg-slate-100 text-slate-700',
            'avatar' => 'bg-slate-200 text-slate-600',
        ],
    ];
@endphp

<div class="space-y-5">

    {{-- =========================================================================
         CARTES KPI
         ========================================================================= --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        @foreach($kpiList as [$label, $value, $backgroundClass, $valueClass, $labelClass])
            <div class="{{ $backgroundClass }} rounded-xl p-4 border border-slate-200">
                <p class="text-2xl font-bold {{ $valueClass }}">{{ $value }}</p>
                <p class="text-xs font-medium {{ $labelClass }} mt-1">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    <div class="card">

        {{-- =========================================================================
             BARRE D’OUTILS : RECHERCHE, FILTRES, ACTION PRINCIPALE
             ========================================================================= --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 p-4 border-b border-slate-200">
            <form
                method="GET"
                action="{{ route('admin.users.index') }}"
                class="flex flex-wrap items-center gap-2 flex-1"
            >
                {{-- Recherche par nom, email ou département --}}
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Nom, email, département…"
                    class="form-input w-52"
                >

                {{-- Filtre rôle --}}
                <select name="role" class="form-input w-36">
                    <option value="">Tous rôles</option>
                    @foreach($roleOptions as $value => $label)
                        <option value="{{ $value }}" {{ request('role') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                {{-- Filtre statut --}}
                <select name="status" class="form-input w-36">
                    <option value="">Tous statuts</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="btn-primary">Filtrer</button>

                @if($hasActiveFilters)
                    {{-- Retour à la liste complète sans filtres --}}
                    <a href="{{ route('admin.users.index') }}" class="btn-secondary">Réinitialiser</a>
                @endif
            </form>

            {{-- Action principale de création --}}
            <a href="{{ route('admin.users.create') }}" class="btn-primary whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Nouvel utilisateur</span>
            </a>
        </div>

        {{-- =========================================================================
             TABLEAU DES UTILISATEURS
             ========================================================================= --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/50 text-left">
                        <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Utilisateur
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Rôle
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Département
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Tickets
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Dernière connexion
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Statut
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        @php
                            $roleView = $roleBadgeMap[$user->role] ?? $roleBadgeMap[\App\Models\User::ROLE_CLIENT];
                        @endphp

                        <tr class="hover:bg-slate-50 transition-colors {{ !$user->is_active ? 'opacity-60' : '' }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0 {{ $roleView['avatar'] }}">
                                        {{-- Utilisation de l’accessor initials pour un affichage homogène --}}
                                        {{ $user->initials }}
                                    </div>

                                    <div class="min-w-0">
                                        <p class="font-medium text-slate-900 truncate">{{ $user->name }}</p>
                                        <p class="text-xs text-slate-400 truncate">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <span class="{{ $roleView['badge'] }}">{{ $roleView['label'] }}</span>
                            </td>

                            <td class="px-4 py-3 text-slate-600 text-sm">
                                {{ $user->department ?: '—' }}
                            </td>

                            <td class="px-4 py-3">
                                <div class="text-xs text-slate-500 space-y-0.5">
                                    <p>{{ $user->tickets_count }} soumis</p>

                                    @if($user->isAgent())
                                        <p class="text-blue-600">{{ $user->assigned_count }} assignés</p>
                                    @endif
                                </div>
                            </td>

                            <td class="px-4 py-3 text-xs text-slate-500">
                                {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Jamais' }}
                            </td>

                            <td class="px-4 py-3">
                                @if($user->is_active)
                                    <span class="badge bg-green-100 text-green-800">Actif</span>
                                @else
                                    <span class="badge bg-red-100 text-red-700">Inactif</span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2 flex-wrap">
                                    {{-- Lien d’édition du compte --}}
                                    <a
                                        href="{{ route('admin.users.edit', $user) }}"
                                        class="text-blue-600 hover:text-blue-800 text-xs font-medium"
                                    >
                                        Éditer
                                    </a>

                                    @if($user->id !== $currentUser->id)
                                        {{-- Activation / désactivation du compte --}}
                                        <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="text-xs font-medium {{ $user->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-green-600 hover:text-green-800' }}"
                                            >
                                                {{ $user->is_active ? 'Désactiver' : 'Activer' }}
                                            </button>
                                        </form>

                                        {{-- Suppression définitive --}}
                                        <form
                                            method="POST"
                                            action="{{ route('admin.users.destroy', $user) }}"
                                            onsubmit="return confirm('Supprimer {{ addslashes($user->name) }} ? Cette action est irréversible.')"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">
                                                Supprimer
                                            </button>
                                        </form>
                                    @else
                                        {{-- On signale clairement le compte connecté --}}
                                        <span class="text-xs text-slate-300">(vous)</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center text-slate-400">
                                <svg class="w-10 h-10 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>

                                <p class="text-sm mb-3">Aucun utilisateur trouvé</p>

                                <a href="{{ route('admin.users.create') }}" class="btn-primary text-xs">
                                    Créer un utilisateur
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
        @if($users->hasPages())
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
