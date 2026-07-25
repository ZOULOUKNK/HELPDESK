<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Déconnexion</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center h-screen bg-slate-50">
<div class="text-center">
    <p class="text-slate-600 mb-4">Cliquez pour vous déconnecter</p>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit"
                class="bg-red-600 text-white px-6 py-2.5 rounded-lg font-medium hover:bg-red-700">
            Se déconnecter
        </button>
    </form>
</div>
</body>
</html>