@extends('layouts.app')

@section('title', 'Créer un utilisateur')
@section('page-title', 'Créer un utilisateur')

@section('content')
@php
    /*
    |--------------------------------------------------------------------------
    | Préparation des données de vue
    |--------------------------------------------------------------------------
    | On prépare les options de rôle pour éviter les chaînes répétées
    | et garder la vue cohérente avec le modèle User.
    */
    $roleOptions = [
        \App\Models\User::ROLE_CLIENT => 'Client',
        \App\Models\User::ROLE_AGENT => 'Agent support',
        \App\Models\User::ROLE_ADMIN => 'Administrateur',
    ];
@endphp

<div class="max-w-2xl mx-auto">

    {{-- =========================================================================
         FIL D’ARIANE
         ========================================================================= --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500 mb-6">
        <a href="{{ route('admin.users.index') }}" class="hover:text-slate-700">Utilisateurs</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-slate-900 font-medium">Nouvel utilisateur</span>
    </nav>

    <div class="card p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-6">Informations du compte</h2>

        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
            @csrf

            {{-- Bloc d’erreurs globales --}}
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
                        value="{{ old('name') }}"
                        required
                        class="form-input @error('name') border-red-400 @enderror"
                        placeholder="Jean Dupont"
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
                        value="{{ old('email') }}"
                        required
                        class="form-input @error('email') border-red-400 @enderror"
                        placeholder="utilisateur@exemple.com"
                    >
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Mot de passe --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Mot de passe <span class="text-red-500">*</span></label>
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
                    <label class="form-label">Confirmer le mot de passe <span class="text-red-500">*</span></label>
                    <input
                        type="password"
                        name="password_confirmation"
                        required
                        class="form-input"
                        placeholder="••••••••"
                    >
                </div>
            </div>

            {{-- Rôle et département --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Rôle <span class="text-red-500">*</span></label>
                    <select name="role" class="form-input @error('role') border-red-400 @enderror">
                        @foreach($roleOptions as $value => $label)
                            <option value="{{ $value }}" {{ old('role', \App\Models\User::ROLE_CLIENT) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">Département</label>
                    <input
                        type="text"
                        name="department"
                        value="{{ old('department') }}"
                        class="form-input @error('department') border-red-400 @enderror"
                        placeholder="Ex: Informatique, RH, Finance…"
                    >
                    @error('department')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Téléphone et activation du compte --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Téléphone</label>
                    <input
                        type="text"
                        name="phone"
                        value="{{ old('phone') }}"
                        class="form-input @error('phone') border-red-400 @enderror"
                        placeholder="+33 6 00 00 00 00"
                    >
                    @error('phone')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-end">
                    <label class="flex items-center gap-3 cursor-pointer pb-2.5">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            {{ old('is_active', true) ? 'checked' : '' }}
                        >
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
                    placeholder="Notes internes sur cet utilisateur…"
                >{{ old('bio') }}</textarea>
                @error('bio')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actions finales du formulaire --}}
            <div class="flex items-center justify-between pt-4 border-t border-slate-200">
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">Annuler</a>

                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Créer l'utilisateur</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection