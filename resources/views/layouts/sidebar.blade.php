@php
    $currentUser = Auth::user();

    $openTicketsQuery = \App\Models\Ticket::query()
        ->where('status', \App\Models\Ticket::STATUS_OPEN);

    if ($currentUser->role === \App\Models\User::ROLE_AGENT) {
        $openTicketsQuery->where(function ($query) use ($currentUser) {
            $query->where('assigned_to', $currentUser->id)
                  ->orWhereNull('assigned_to');
        });
    } elseif ($currentUser->isClient()) {
        $openTicketsQuery->where('user_id', $currentUser->id);
    }

    $openCount = $openTicketsQuery->count();

    $unassignedCount = $currentUser->isAgent()
        ? \App\Models\Ticket::query()
            ->whereNull('assigned_to')
            ->where('status', \App\Models\Ticket::STATUS_OPEN)
            ->count()
        : 0;

    $criticalCount = $currentUser->isAgent()
        ? \App\Models\Ticket::query()
            ->where('priority', \App\Models\Ticket::PRIORITY_CRITICAL)
            ->whereNotIn('status', \App\Models\Ticket::RESOLVED_STATUSES)
            ->count()
        : 0;

    $inactiveUsersCount = $currentUser->isAdmin()
        ? \App\Models\User::query()->where('is_active', false)->count()
        : 0;

    if ($currentUser->isSuperAdmin()) {
        $currentUserAvatarClass = 'bg-fuchsia-100 text-fuchsia-700';
        $currentUserRoleLabel = 'Super administrateur';
        $currentUserRoleTextClass = 'text-fuchsia-600';
    } elseif ($currentUser->isAdmin()) {
        $currentUserAvatarClass = 'bg-purple-100 text-purple-700';
        $currentUserRoleLabel = 'Administrateur';
        $currentUserRoleTextClass = 'text-purple-600';
    } elseif ($currentUser->isAgent()) {
        $currentUserAvatarClass = 'bg-blue-100 text-blue-700';
        $currentUserRoleLabel = 'Agent support';
        $currentUserRoleTextClass = 'text-blue-600';
    } else {
        $currentUserAvatarClass = 'bg-slate-200 text-slate-600';
        $currentUserRoleLabel = 'Client';
        $currentUserRoleTextClass = 'text-slate-400';
    }
@endphp

<div class="flex flex-col h-full">

    <div class="flex items-center gap-3 px-5 h-16 border-b border-slate-200 flex-shrink-0">
        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2.5"
                    d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"
                />
            </svg>
        </div>

        <div>
            <p class="text-sm font-bold text-slate-900 leading-none">Helpdesk</p>
            <p class="text-xs text-slate-400 mt-0.5">Support Technique</p>
        </div>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">

        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span>Tableau de bord</span>
        </a>

        <a href="{{ route('tickets.index') }}" class="nav-link {{ request()->routeIs('tickets.index') || request()->routeIs('tickets.show') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>Tickets</span>

            @if($openCount > 0)
                <span class="ml-auto text-xs font-semibold bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">
                    {{ $openCount }}
                </span>
            @endif
        </a>

        <a href="{{ route('tickets.create') }}" class="nav-link {{ request()->routeIs('tickets.create') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Nouveau ticket</span>
        </a>

        @if($currentUser->isAgent())
            <div class="pt-5 pb-2">
                <p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Gestion</p>
            </div>

            <a href="{{ route('tickets.index', ['assigned_to' => 'none']) }}" class="nav-link {{ request('assigned_to') === 'none' ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <span>Non assignés</span>

                @if($unassignedCount > 0)
                    <span class="ml-auto text-xs font-semibold bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full">
                        {{ $unassignedCount }}
                    </span>
                @endif
            </a>

            <a href="{{ route('tickets.index', ['priority' => \App\Models\Ticket::PRIORITY_CRITICAL]) }}" class="nav-link {{ request('priority') === \App\Models\Ticket::PRIORITY_CRITICAL ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>Critiques</span>

                @if($criticalCount > 0)
                    <span class="ml-auto text-xs font-semibold bg-red-100 text-red-600 px-2 py-0.5 rounded-full">
                        {{ $criticalCount }}
                    </span>
                @endif
            </a>

            <a href="{{ route('tickets.index', ['status' => \App\Models\Ticket::STATUS_IN_PROGRESS]) }}" class="nav-link {{ request('status') === \App\Models\Ticket::STATUS_IN_PROGRESS ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>En cours</span>
            </a>
        @endif

        @if($currentUser->isAdmin())
            <div class="pt-5 pb-2">
                <p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Administration</p>
            </div>

            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Utilisateurs</span>

                @if($inactiveUsersCount > 0)
                    <span class="ml-auto text-xs font-semibold bg-red-100 text-red-600 px-2 py-0.5 rounded-full">
                        {{ $inactiveUsersCount }}
                    </span>
                @endif
            </a>

            @if($currentUser->isSuperAdmin())
                <a href="{{ route('admin.stats') }}" class="nav-link {{ request()->routeIs('admin.stats') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Vue direction</span>
                </a>

                <a href="{{ route('admin.settings.edit') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317a1 1 0 011.35-.936l1.03.427a1 1 0 00.95-.05l.98-.57a1 1 0 011.34.366l.55.952a1 1 0 00.79.49l1.09.13a1 1 0 01.88 1.08l-.1 1.09a1 1 0 00.38.87l.84.69a1 1 0 01.16 1.4l-.68.86a1 1 0 00-.2.93l.32 1.05a1 1 0 01-.64 1.25l-1.04.34a1 1 0 00-.69.66l-.38 1.03a1 1 0 01-1.27.6l-1.03-.34a1 1 0 00-.92.16l-.89.64a1 1 0 01-1.39-.2l-.66-.85a1 1 0 00-.87-.38l-1.09.09a1 1 0 01-1.07-.88l-.14-1.09a1 1 0 00-.5-.79l-.95-.54a1 1 0 01-.37-1.34l.56-.98a1 1 0 00.05-.95l-.43-1.03a1 1 0 01.94-1.35l1.11-.03a1 1 0 00.82-.45l.63-.89z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Configuration</span>
                </a>
            @endif
        @endif
    </nav>

    <div class="border-t border-slate-200 mx-3"></div>

    <div class="p-3 flex-shrink-0">
        <div class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-slate-100 transition-colors">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 {{ $currentUserAvatarClass }}">
                {{ $currentUser->initials }}
            </div>

            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-slate-900 truncate leading-none">
                    {{ $currentUser->name }}
                </p>
                <p class="text-xs mt-0.5 font-medium {{ $currentUserRoleTextClass }}">
                    {{ $currentUserRoleLabel }}
                </p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Se déconnecter" class="text-slate-400 hover:text-red-500 transition-colors p-1 rounded">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>