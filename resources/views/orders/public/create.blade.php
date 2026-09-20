<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Commander — Chez Traoré</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-stone-100 text-gray-900">

    {{-- En-tête --}}
    <header class="bg-stone-900 text-white">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="/" class="leading-tight">
                <div class="text-xl font-semibold">Chez <span class="text-amber-400">Traoré</span></div>
                <div class="text-xs text-amber-300 tracking-widest">COMMANDER À EMPORTER</div>
            </a>
            <a href="/" class="text-sm text-stone-300 hover:text-white">← Accueil</a>
        </div>
    </header>

    <div class="max-w-6xl mx-auto px-4 py-6" x-data="orderPos(@js($productsData))">

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md mb-4">{{ $errors->first() }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            {{-- Menu --}}
            <div class="lg:col-span-2 space-y-3">
                <div class="bg-white shadow rounded-lg p-3 flex flex-wrap gap-2">
                    <input type="text" x-model="search" placeholder="Rechercher un plat…"
                        class="border-gray-300 rounded-md shadow-sm w-full max-w-xs" />
                    <select x-model="category" class="border-gray-300 rounded-md shadow-sm">
                        <option value="">Toutes catégories</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <template x-for="p in filteredProducts" :key="p.id">
                        <button type="button" x-on:click="add(p)"
                            class="bg-white rounded-lg shadow overflow-hidden text-left hover:ring-2 hover:ring-amber-500 focus:outline-none">
                            <img :src="p.image" :alt="p.name" class="h-28 w-full object-cover" />
                            <div class="p-2">
                                <div class="text-sm font-medium text-gray-900 leading-tight" x-text="p.name"></div>
                                <div class="text-sm text-amber-700 font-semibold mt-1" x-text="money(p.price)"></div>
                            </div>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Panier + coordonnées --}}
            <div class="lg:col-span-1">
                <div class="bg-white shadow rounded-lg p-4 lg:sticky lg:top-4">
                    <h2 class="font-semibold text-gray-800 mb-3">Ma commande</h2>

                    <div class="divide-y divide-gray-100">
                        <template x-for="line in cart" :key="line.id">
                            <div class="flex items-center gap-2 py-2">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-900 truncate" x-text="line.name"></div>
                                    <div class="text-xs text-gray-500" x-text="money(line.price)"></div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button type="button" x-on:click="dec(line)" class="w-7 h-7 rounded bg-gray-100 hover:bg-gray-200">−</button>
                                    <span class="w-6 text-center text-sm" x-text="line.qty"></span>
                                    <button type="button" x-on:click="inc(line)" class="w-7 h-7 rounded bg-gray-100 hover:bg-gray-200">+</button>
                                </div>
                                <div class="w-20 text-right text-sm font-medium" x-text="money(line.price * line.qty)"></div>
                            </div>
                        </template>
                    </div>

                    <div x-show="cart.length === 0" class="text-sm text-gray-500 py-6 text-center">
                        Touche un plat pour l'ajouter à ta commande.
                    </div>

                    <div class="flex justify-between items-center border-t border-gray-200 mt-3 pt-3">
                        <span class="font-semibold text-gray-800">Total</span>
                        <span class="text-xl font-bold text-gray-900" x-text="money(total)"></span>
                    </div>

                    <form method="POST" action="{{ route('order.store') }}" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <label class="block text-sm text-gray-600">Votre nom</label>
                            <input type="text" name="customer_name" value="{{ old('customer_name') }}" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Ex. Moussa" />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600">Votre téléphone</label>
                            <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Ex. 49 62 53 25" />
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600">Note (facultatif)</label>
                            <input type="text" name="note" value="{{ old('note') }}"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Ex. sans piment" />
                        </div>

                        <template x-for="(line, idx) in cart" :key="'h' + line.id">
                            <div>
                                <input type="hidden" :name="`items[${idx}][product_id]`" :value="line.id" />
                                <input type="hidden" :name="`items[${idx}][quantity]`" :value="line.qty" />
                            </div>
                        </template>

                        <button type="submit" x-bind:disabled="cart.length === 0"
                            class="w-full px-4 py-3 bg-amber-500 text-stone-900 font-semibold rounded-md hover:bg-amber-400 disabled:opacity-50 disabled:cursor-not-allowed">
                            Envoyer ma commande
                        </button>
                        <p class="text-xs text-gray-500 text-center">Paiement au retrait. On vous rappelle pour confirmer. 📞</p>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function orderPos(products) {
                return {
                    products: products,
                    search: '',
                    category: '',
                    cart: [],
                    // Accents ignorés et mots dans n'importe quel ordre :
                    // « hachee » trouve « Kebab haché », « ke po » trouve « Kebab poulet ».
                    norm(v) {
                        return (v ?? '').toString().toLowerCase()
                            .normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                    },
                    get filteredProducts() {
                        const words = this.norm(this.search).trim().split(/\s+/).filter(Boolean);
                        return this.products.filter(p =>
                            (this.category === '' || String(p.category_id) === String(this.category)) &&
                            (words.length === 0 || words.every(w => this.norm(p.name).includes(w)))
                        );
                    },
                    add(p) {
                        const line = this.cart.find(l => l.id === p.id);
                        if (line) { if (line.qty < 50) line.qty++; } else { this.cart.push({ id: p.id, name: p.name, price: p.price, qty: 1 }); }
                    },
                    inc(line) { if (line.qty < 50) line.qty++; },
                    dec(line) { line.qty--; if (line.qty <= 0) this.cart = this.cart.filter(l => l.id !== line.id); },
                    get total() { return this.cart.reduce((s, l) => s + l.price * l.qty, 0); },
                    money(v) { return new Intl.NumberFormat('fr-FR').format(v) + ' MRU'; },
                };
            }
        </script>
    </div>
</body>
</html>
