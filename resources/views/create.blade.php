<!DOCTYPE html>
<html>
<head>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-100 p-10">

<div class="max-w-xl mx-auto bg-white p-6 rounded-xl shadow">

    <h1 class="text-2xl font-bold mb-4">Créer un ticket</h1>

    <form method="POST" action="/tickets">
        @csrf

        <input type="text" name="title"
            class="w-full border p-2 mb-3 rounded"
            placeholder="Titre">

        <textarea name="description"
            class="w-full border p-2 mb-3 rounded"
            placeholder="Description"></textarea>

        <button class="bg-blue-500 text-white px-4 py-2 rounded">
            Envoyer
        </button>
    </form>

</div>

</body>
</html>