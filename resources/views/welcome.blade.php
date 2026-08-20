<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chez Traoré — Gestion</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-stone-900 text-stone-100 flex flex-col">
        <div class="flex-1 flex flex-col items-center justify-center text-center px-6">
            <div class="mb-3 text-amber-400 tracking-[0.3em] text-xs uppercase">Bienvenue</div>
            <h1 class="text-5xl sm:text-7xl font-semibold text-white">Chez Traoré</h1>
            <div class="mt-4 h-px w-24 bg-amber-500"></div>
            <p class="mt-4 text-amber-300 text-sm tracking-widest">BANKILY · MASRIVI · SEDAD</p>
            <p class="mt-6 max-w-md text-stone-400 leading-relaxed">
                Application de gestion du restaurant — ventes, dépenses, stock et caisse.
                Gardez le contrôle de votre argent, chaque jour.
            </p>

            <div class="mt-10">
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="inline-block px-8 py-3 rounded-md bg-amber-500 text-stone-900 font-semibold hover:bg-amber-400 transition">
                        Ouvrir le tableau de bord
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="inline-block px-8 py-3 rounded-md bg-amber-500 text-stone-900 font-semibold hover:bg-amber-400 transition">
                        Se connecter
                    </a>
                @endauth
            </div>
        </div>

        <footer class="text-center text-stone-500 text-xs py-6">
            © {{ date('Y') }} Chez Traoré · 49 62 53 25
        </footer>
    </div>
</body>
</html>
