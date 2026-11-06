@extends('layouts.app')

@section('title', 'Mon compte')
@section('page-title', 'Mon compte')

@section('content')
<div class="max-w-2xl mx-auto space-y-5">

    {{-- =========================================================================
         PROFIL
         ========================================================================= --}}
    <div class="card p-6">
        <h2 class="text-base font-semibold text-slate-900">Profil</h2>
        <p class="text-xs text-slate-500 mt-1 mb-5">
            Connecté avec <span class="font-medium text-slate-700">{{ $user->email }}</span>.
            L'adresse e-mail et le rôle sont gérés par un administrateur.
        </p>

        <form method="POST" action="{{ route('preferences.profile') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="form-label">Nom complet <span class="text-red-500">*</span></label>
                    <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required
                        class="form-input @error('name') border-red-400 @enderror">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="form-label">Téléphone</label>
                    <input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                        class="form-input @error('phone') border-red-400 @enderror">
                    @error('phone')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="bio" class="form-label">Présentation</label>
                <textarea id="bio" name="bio" rows="3"
                    class="form-input resize-none @error('bio') border-red-400 @enderror">{{ old('bio', $user->bio) }}</textarea>
                @error('bio')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Enregistrer le profil</button>
            </div>
        </form>
    </div>

    {{-- =========================================================================
         MOT DE PASSE
         ========================================================================= --}}
    <div class="card p-6">
        <h2 class="text-base font-semibold text-slate-900">Mot de passe</h2>
        <p class="text-xs text-slate-500 mt-1 mb-5">
            Vos autres sessions ouvertes seront déconnectées.
        </p>

        <form method="POST" action="{{ route('preferences.password') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="form-label">Mot de passe actuel</label>
                <input id="current_password" type="password" name="current_password" autocomplete="current-password" required
                    class="form-input @error('current_password') border-red-400 @enderror">
                @error('current_password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="form-label">Nouveau mot de passe</label>
                    <input id="password" type="password" name="password" autocomplete="new-password" required
                        placeholder="Minimum 8 caractères"
                        class="form-input @error('password') border-red-400 @enderror">
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required
                        class="form-input">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Changer le mot de passe</button>
            </div>
        </form>
    </div>

    {{-- =========================================================================
         PRÉFÉRENCES
         ========================================================================= --}}
    <div class="card p-6">
        <h2 class="text-base font-semibold text-slate-900 mb-5">Préférences</h2>

        <form method="POST" action="{{ route('preferences.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="home_page" class="form-label">Page d'accueil après connexion</label>
                    <select id="home_page" name="home_page" class="form-input @error('home_page') border-red-400 @enderror">
                        @foreach($homePages as $value => $label)
                            <option value="{{ $value }}" @selected(old('home_page', $settings['home_page']) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('home_page')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="tickets_per_page" class="form-label">Tickets par page</label>
                    <input id="tickets_per_page" type="number" name="tickets_per_page" min="5" max="100"
                        value="{{ old('tickets_per_page', $settings['tickets_per_page']) }}"
                        class="form-input @error('tickets_per_page') border-red-400 @enderror">
                    @error('tickets_per_page')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="timezone" class="form-label">Fuseau horaire</label>
                    <select id="timezone" name="timezone" class="form-input @error('timezone') border-red-400 @enderror">
                        @foreach($timezones as $value => $label)
                            <option value="{{ $value }}" @selected(old('timezone', $settings['timezone']) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('timezone')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="accent_color" class="form-label">Couleur d'accent</label>
                    <div class="flex items-center gap-3">
                        <input id="accent_color" type="color" name="accent_color"
                            value="{{ old('accent_color', $settings['accent_color']) }}"
                            class="h-10 w-14 rounded border border-slate-300 cursor-pointer">
                        <span class="text-xs text-slate-500">Boutons, liens et éléments actifs.</span>
                    </div>
                    @error('accent_color')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Enregistrer les préférences</button>
            </div>
        </form>
    </div>
</div>
@endsection
