@extends('layouts.app')

@section('title', 'Éditer ' . $user->name)
@section('page-title', 'Éditer l\'utilisateur')

@section('content')
@php
    /*
    |--------------------------------------------------------------------------
    | Préparation des données de vue
    |--------------------------------------------------------------------------
    | On prépare ici les éléments d'affichage pour limiter les répétitions
    | et garder le template lisible.
    */
    $currentUser = auth()->user();

    $isSelf = $user->id === $currentUser->id;

    $roleOptions = [
        \App\Models\User::ROLE_CLIENT => 'Client',
        \App\Models\User::ROLE_AGENT => 'Agent support',
        \App\Models\User::ROLE_ADMIN => 'Administrateur',
    ];

    $roleColorMap = [
        \App\Models\User::ROLE_ADMIN => 'bg-purple-100 text-purple-700',
        \App\Models\User::ROLE_AGENT => 'bg-blue-100 text-blue-700',
        \App\Models\User::ROLE_CLIENT => 'bg-slate-200 text-slate-600',
    ];

    $avatarClass = $roleColorMap[$user->role] ?? $roleColorMap[\App\Models\User::ROLE_CLIENT];
@endphp

<div class="max-w-2xl mx-auto space-y-5">

    {{-- =========================================================================
         FIL D’ARIANE
         ========================================================================= --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('admin.users.index') }}" class="hover:text-slate-700">Utilisateurs</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-slate-900 font-medium">{{ $user->name }}</span>
    </nav>

    {{-- =========================================================================
         CARTE PROFIL RAPIDE
         ========================================================================= --}}
    <div class="card p-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full flex items-center justify-center text-xl font-bold flex-shrink-0 {{ $avatarClass }}">
                {{-- Utilisation de l’accessor initials pour un rendu uniforme --}}
                {{ $user->initials }}
            </div>

            <div class="flex-1">
                <h3 class="font-semibold text-slate-900">{{ $user->name }}</h3>
                <p class="text-sm text-slate-500">{{ $user->email }}</p>
                <p class="text-xs text-slate-400 mt-1">
                    Membre depuis {{ $user->created_at->format('d/m/Y') }}
                    · Dernière connexion : {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'jamais' }}
                </p>
            </div>

            <div class="text-right text-xs space-y-1">
                <p class="text-slate-600">
                    <span class="font-semibold text-slate-900">{{ $ticketStats['total'] }}</span> tickets soumis
                </p>
                <p class="text-slate-600">
                    <span class="font-semibold text-green-700">{{ $ticketStats['resolved'] }}</span> résolus
                </p>

                @if($user->isAgent())
                    <p class="text-slate-600">
                        <span class="font-semibold text-blue-700">{{ $ticketStats['assigned'] }}</span> en charge
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- =========================================================================
         FORMULAIRE PRINCIPAL
         ========================================================================= --}}
    <div class="card p-6">
        <h2 class="text-base font-semibold text-slate-900 mb-5">Informations du compte</h2>

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-5">
            @csrf
            @method('PUT')

            {{-- Bloc d’erreurs globales éventuelles --}}
            @if($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="space-y-1">
                        @foreach($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Identité principale --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Nom complet <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        required
                        class="form-input @error('name') border-red-400 @enderror"
                    >
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Adresse e-mail <span class="text-red-500">*</span></label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        required
                        class="form-input @error('email') border-red-400 @enderror"
                    >
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Rôle et département --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Rôle <span class="text-red-500">*</span></label>

                    <select
                        name="role"
                        class="form-input @error('role') border-red-400 @enderror"
                        {{ $isSelf ? 'disabled' : '' }}
                    >
                        @foreach($roleOptions as $value => $label)
                            <option value="{{ $value }}" {{ old('role', $user->role) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    @error('role')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror

                    @if($isSelf)
                        {{-- On conserve la valeur réelle si le select est désactivé --}}
                        <input type="hidden" name="role" value="{{ $user->role }}">
                        <p class="mt-1 text-xs text-slate-400">Vous ne pouvez pas modifier votre propre rôle.</p>
                    @endif
                </div>

                <div>
                    <label class="form-label">Département</label>
                    <input
                        type="text"
                        name="department"
                        value="{{ old('department', $user->department) }}"
                        class="form-input @error('department') border-red-400 @enderror"
                        placeholder="Ex: Informatique"
                    >
                    @error('department')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Téléphone et état du compte --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Téléphone</label>
                    <input
                        type="text"
                        name="phone"
                        value="{{ old('phone', $user->phone) }}"
                        class="form-input @error('phone') border-red-400 @enderror"
                        placeholder="+33 6 00 00 00 00"
                    >
                    @error('phone')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center pt-6">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                            {{ $isSelf ? 'disabled' : '' }}
                        >

                        @if($isSelf)
                            {{-- Un champ hidden maintient le compte courant actif dans le formulaire --}}
                            <input type="hidden" name="is_active" value="1">
                        @endif

                        <div>
                            <p class="text-sm font-medium text-slate-700">Compte actif</p>
                            <p class="text-xs text-slate-400">L'utilisateur peut se connecter</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Notes internes --}}
            <div>
                <label class="form-label">Biographie / Notes</label>
                <textarea
                    name="bio"
                    rows="3"
                    class="form-input resize-none @error('bio') border-red-400 @enderror"
                    placeholder="Notes internes…"
                >{{ old('bio', $user->bio) }}</textarea>
                @error('bio')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nouveau mot de passe optionnel dans la mise à jour --}}
            <div class="pt-4 border-t border-slate-200 space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Mot de passe</h3>
                    <p class="text-xs text-slate-500 mt-1">
                        Laissez ces champs vides pour conserver le mot de passe actuel.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Nouveau mot de passe</label>
                        <input
                            type="password"
                            name="password"
                            class="form-input @error('password') border-red-400 @enderror"
                            placeholder="Minimum 8 caractères"
                        >
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Confirmer le mot de passe</label>
                        <input
                            type="password"
                            name="password_confirmation"
                            class="form-input"
                            placeholder="••••••••"
                        >
                    </div>
                </div>
            </div>

            {{-- Actions formulaire --}}
            <div class="flex items-center justify-between pt-4 border-t border-slate-200">
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary">Enregistrer les modifications</button>
            </div>
        </form>
    </div>

    {{-- =========================================================================
         RÉINITIALISATION DÉDIÉE DU MOT DE PASSE
         ========================================================================= --}}
    <div class="card p-6" x-data="{ open: false }">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Réinitialiser le mot de passe</h2>
                <p class="text-sm text-slate-500 mt-0.5">Définir un nouveau mot de passe pour cet utilisateur.</p>
            </div>

            <button @click="open = !open" type="button" class="btn-secondary text-sm">
                Modifier
            </button>
        </div>

        <div x-show="open" x-cloak class="mt-5 pt-5 border-t border-slate-200">
            <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Nouveau mot de passe</label>
                        <input
                            type="password"
                            name="password"
                            required
                            class="form-input @error('password') border-red-400 @enderror"
                            placeholder="Minimum 8 caractères"
                        >
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label">Confirmer</label>
                        <input
                            type="password"
                            name="password_confirmation"
                            required
                            class="form-input"
                            placeholder="••••••••"
                        >
                    </div>
                </div>

                <button type="submit" class="btn-danger">
                    Réinitialiser le mot de passe
                </button>
            </form>
        </div>
    </div>

    {{-- =========================================================================
         ZONE DE DANGER
         ========================================================================= --}}
    @if(!$isSelf)
        <div class="card p-6 border-red-200">
            <h2 class="text-base font-semibold text-red-700 mb-1">Zone de danger</h2>
            <p class="text-sm text-slate-500 mb-4">Ces actions sont irréversibles.</p>

            <div class="flex items-center gap-3 flex-wrap">
                {{-- Activation / désactivation rapide du compte --}}
                <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                    @csrf
                    <button
                        type="submit"
                        class="{{ $user->is_active ? 'btn-danger' : 'btn-secondary' }} text-sm"
                    >
                        {{ $user->is_active ? 'Désactiver le compte' : 'Réactiver le compte' }}
                    </button>
                </form>

                {{-- Suppression définitive du compte --}}
                <form
                    method="POST"
                    action="{{ route('admin.users.destroy', $user) }}"
                    onsubmit="return confirm('Supprimer définitivement {{ addslashes($user->name) }} ?')"
                >
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn-danger text-sm">
                        Supprimer définitivement
                    </button>
                </form>
            </div>
        </div>
    @endif

</div>
@endsection