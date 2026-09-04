<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Commande reçue — Chez Traoré</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-stone-900 text-stone-100">
    <div class="min-h-screen flex flex-col items-center justify-center text-center px-6">
        <div class="text-5xl mb-4">✅</div>
        <h1 class="text-2xl sm:text-3xl font-semibold text-white">Merci {{ $info['name'] }} !</h1>
        <p class="mt-3 text-stone-300 max-w-md">
            Ta commande a bien été reçue. <strong class="text-amber-300">On t'appelle très vite</strong> pour la confirmer.
        </p>

        <div class="mt-6 bg-stone-800 rounded-lg px-6 py-4">
            <div class="text-xs text-stone-400 uppercase tracking-widest">Numéro de commande</div>
            <div class="text-xl font-bold text-amber-400 font-mono">{{ $info['number'] }}</div>
            <div class="mt-2 text-sm text-stone-300">Total : <strong>{{ number_format($info['total'], 0, ',', ' ') }} MRU</strong> · à régler au retrait</div>
        </div>

        <div class="mt-8 flex gap-3">
            <a href="{{ route('order.create') }}" class="px-6 py-3 rounded-md bg-amber-500 text-stone-900 font-semibold hover:bg-amber-400">Commander à nouveau</a>
            <a href="/" class="px-6 py-3 rounded-md border border-stone-600 text-stone-200 hover:bg-stone-800">Accueil</a>
        </div>

        <p class="mt-8 text-stone-500 text-sm">Chez Traoré · 49 62 53 25</p>
    </div>
</body>
</html>
