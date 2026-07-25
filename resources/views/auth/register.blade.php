<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — Helpdesk</title>

    {{-- Chargement Tailwind via CDN pour l’interface d’inscription --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Police principale --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="h-full flex items-center justify-center p-4 bg-slate-50">
<div class="w-full max-w-sm">

    {{-- =========================================================================
         EN-TÊTE
         ========================================================================= --}}
    <div class="text-center mb-8">
        <div class="w-14 h-14 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-slate-900">Créer un compte</h1>
        <p class="text-sm text-slate-500 mt-1">Accédez au portail de support</p>
    </div>

    {{-- =========================================================================
         CARTE D’INSCRIPTION
         ========================================================================= --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

        {{-- Affichage des erreurs globales --}}
        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700">
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            {{-- Nom complet --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Nom complet</label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all @error('name') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                    placeholder="Jean Dupont"
                >
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Adresse e-mail --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Adresse e-mail</label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all @error('email') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                    placeholder="vous@exemple.com"
                >
                @error('email')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Mot de passe --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Mot de passe</label>
                <input
                    type="password"
                    name="password"
                    required
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all @error('password') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                    placeholder="Minimum 8 caractères"
                >
                @error('password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirmation du mot de passe --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirmer le mot de passe</label>
                <input
                    type="password"
                    name="password_confirmation"
                    required
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all"
                    placeholder="••••••••"
                >
            </div>

            {{-- Action principale --}}
            <button
                type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm"
            >
                Créer mon compte
            </button>
        </form>
    </div>

    {{-- =========================================================================
         LIEN DE RETOUR VERS LA CONNEXION
         ========================================================================= --}}
    <p class="text-center text-sm text-slate-500 mt-5">
        Déjà un compte ?
        <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800 font-medium">
            Se connecter
        </a>
    </p>
</div>
</body>
</html>