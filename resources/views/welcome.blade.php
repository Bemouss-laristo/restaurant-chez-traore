<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chez Traoré — Restaurant à Nouakchott</title>
    <meta name="description" content="Chez Traoré — grillades, pizzas, tacos, kebabs et desserts à Nouakchott. Commandez en ligne, à emporter.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-stone-100 text-gray-900">

    {{-- Barre de navigation --}}
    <header class="bg-stone-900 text-white sticky top-0 z-10">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="text-xl font-semibold">Chez <span class="text-amber-400">Traoré</span></div>
            <a href="{{ route('order.create') }}"
                class="px-4 py-2 rounded-md bg-amber-500 text-stone-900 text-sm font-semibold hover:bg-amber-400">Commander</a>
        </div>
    </header>

    {{-- Héro --}}
    <section class="bg-stone-900 text-stone-100">
        <div class="max-w-6xl mx-auto px-4 py-16 sm:py-24 text-center">
            <div class="text-amber-400 tracking-[0.3em] text-xs uppercase mb-3">Bienvenue</div>
            <h1 class="text-4xl sm:text-6xl font-semibold text-white">Le goût qui rassemble</h1>
            <div class="mt-5 h-px w-24 bg-amber-500 mx-auto"></div>
            <p class="mt-6 max-w-xl mx-auto text-stone-300 leading-relaxed text-lg">
                Grillades, pizzas, tacos, kebabs et douceurs maison — préparés avec soin,
                chaque soir, rien que pour vous. Commandez en quelques clics, récupérez, régalez-vous.
            </p>
            <div class="mt-9 flex flex-wrap gap-3 justify-center">
                <a href="{{ route('order.create') }}"
                    class="px-8 py-3 rounded-md bg-amber-500 text-stone-900 font-semibold hover:bg-amber-400 transition">
                    Commander en ligne
                </a>
                <a href="#menu"
                    class="px-8 py-3 rounded-md border border-stone-600 text-stone-200 hover:bg-stone-800 transition">
                    Découvrir le menu
                </a>
            </div>
            <p class="mt-5 text-amber-300 text-sm tracking-widest">BANKILY · MASRIVI · SEDAD · ESPÈCES</p>
        </div>
    </section>

    {{-- Spécialités --}}
    <section id="menu" class="py-16">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center mb-10">
                <h2 class="text-3xl font-semibold text-gray-900">Nos spécialités</h2>
                <p class="mt-2 text-gray-500">Un aperçu de ce qui vous attend.</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                @foreach ($featured as $product)
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="h-36 w-full object-cover" />
                        <div class="p-3">
                            <div class="font-medium text-gray-900">{{ $product->name }}</div>
                            <div class="text-amber-700 font-semibold mt-1">@mru($product->sale_price)</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-center mt-10">
                <a href="{{ route('order.create') }}"
                    class="inline-block px-8 py-3 rounded-md bg-stone-900 text-white font-semibold hover:bg-stone-800 transition">
                    Voir tout le menu & commander
                </a>
            </div>
        </div>
    </section>

    {{-- Infos pratiques --}}
    <section class="bg-white border-t border-gray-100 py-14">
        <div class="max-w-6xl mx-auto px-4 grid grid-cols-1 sm:grid-cols-3 gap-8 text-center">
            <div>
                <div class="text-3xl mb-2">🕖</div>
                <div class="font-medium text-gray-900">Horaires</div>
                <div class="text-gray-500 text-sm mt-1">Tous les soirs, à partir de 19h</div>
            </div>
            <div>
                <div class="text-3xl mb-2">💳</div>
                <div class="font-medium text-gray-900">Paiement au retrait</div>
                <div class="text-gray-500 text-sm mt-1">Espèces · Bankily · Masrivi · Sedad</div>
            </div>
            <div>
                <div class="text-3xl mb-2">📞</div>
                <div class="font-medium text-gray-900">Nous joindre</div>
                <div class="text-gray-500 text-sm mt-1">49 62 53 25 · Nouakchott</div>
            </div>
        </div>
    </section>

    {{-- Pied de page --}}
    <footer class="bg-stone-900 text-stone-400">
        <div class="max-w-6xl mx-auto px-4 py-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm">
            <div>© {{ date('Y') }} Chez Traoré · Nouakchott · 49 62 53 25</div>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="text-stone-500 hover:text-amber-400">Espace équipe</a>
        </div>
    </footer>
</body>
</html>
