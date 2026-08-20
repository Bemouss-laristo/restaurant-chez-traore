<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nouvelle vente</h2>
            <a href="{{ route('sales.index') }}" class="text-sm text-indigo-600 hover:underline">Historique des ventes</a>
        </div>
    </x-slot>

    <div class="py-8" x-data="pos(@js($productsData))">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">{{ $errors->first() }}</div>
            @endif
            @unless ($hasOpenSession)
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-md">
                    ⚠ Aucune caisse ouverte : les ventes en espèces ne seront pas comptées dans la caisse.
                    <a href="{{ route('caisse.index') }}" class="underline">Ouvrir la caisse</a>
                </div>
            @endunless

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                {{-- Produits --}}
                <div class="lg:col-span-2 space-y-3">
                    <div class="bg-white shadow sm:rounded-lg p-3 flex flex-wrap gap-2">
                        <input type="text" x-model="search" placeholder="Rechercher…"
                            class="border-gray-300 rounded-md shadow-sm w-full max-w-xs" />
                        <select x-model="category" class="border-gray-300 rounded-md shadow-sm">
                            <option value="">Toutes catégories</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                        <template x-for="p in filteredProducts" :key="p.id">
                            <button type="button" x-on:click="add(p)"
                                class="bg-white rounded-lg shadow overflow-hidden text-left hover:ring-2 hover:ring-indigo-500 focus:outline-none">
                                <img :src="p.image" :alt="p.name" class="h-24 w-full object-cover" />
                                <div class="p-2">
                                    <div class="text-sm font-medium text-gray-900 leading-tight" x-text="p.name"></div>
                                    <div class="text-sm text-gray-600 mt-1" x-text="money(p.price)"></div>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Panier --}}
                <div class="lg:col-span-1">
                    <div class="bg-white shadow sm:rounded-lg p-4 lg:sticky lg:top-4">
                        <h3 class="font-medium text-gray-800 mb-3">Panier</h3>

                        <div class="divide-y divide-gray-100">
                            <template x-for="line in cart" :key="line.id">
                                <div class="flex items-center gap-2 py-2">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900 truncate" x-text="line.name"></div>
                                        <div class="text-xs text-gray-500" x-text="money(line.price)"></div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button type="button" x-on:click="dec(line)"
                                            class="w-7 h-7 rounded bg-gray-100 hover:bg-gray-200 text-gray-700">−</button>
                                        <span class="w-6 text-center text-sm" x-text="line.qty"></span>
                                        <button type="button" x-on:click="inc(line)"
                                            class="w-7 h-7 rounded bg-gray-100 hover:bg-gray-200 text-gray-700">+</button>
                                    </div>
                                    <div class="w-20 text-right text-sm font-medium" x-text="money(line.price * line.qty)"></div>
                                </div>
                            </template>
                        </div>

                        <div x-show="cart.length === 0" class="text-sm text-gray-500 py-6 text-center">
                            Panier vide. Touche un produit pour l'ajouter.
                        </div>

                        <div class="flex justify-between items-center border-t border-gray-200 mt-3 pt-3">
                            <span class="font-semibold text-gray-800">Total</span>
                            <span class="text-xl font-bold text-gray-900" x-text="money(total)"></span>
                        </div>

                        <form method="POST" action="{{ route('sales.store') }}" class="mt-4 space-y-3">
                            @csrf
                            <div>
                                <x-input-label for="payment_method" value="Mode de paiement" />
                                <select id="payment_method" name="payment_method" x-model="paymentMethod"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    @foreach (\App\Enums\PaymentMethod::cases() as $pm)
                                        <option value="{{ $pm->value }}">{{ $pm->label() }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <template x-for="(line, idx) in cart" :key="'h' + line.id">
                                <div>
                                    <input type="hidden" :name="`items[${idx}][product_id]`" :value="line.id" />
                                    <input type="hidden" :name="`items[${idx}][quantity]`" :value="line.qty" />
                                </div>
                            </template>

                            <button type="submit" x-bind:disabled="cart.length === 0"
                                class="w-full px-4 py-3 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                Valider la vente
                            </button>
                            <button type="button" x-on:click="clear()" x-show="cart.length > 0"
                                class="w-full px-4 py-2 text-sm text-gray-600 hover:underline">Vider le panier</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function pos(products) {
                return {
                    products: products,
                    search: '',
                    category: '',
                    cart: [],
                    paymentMethod: 'especes',
                    get filteredProducts() {
                        const s = this.search.toLowerCase();
                        return this.products.filter(p =>
                            (this.category === '' || String(p.category_id) === String(this.category)) &&
                            (s === '' || p.name.toLowerCase().includes(s))
                        );
                    },
                    add(p) {
                        const line = this.cart.find(l => l.id === p.id);
                        if (line) { line.qty++; }
                        else { this.cart.push({ id: p.id, name: p.name, price: p.price, qty: 1 }); }
                    },
                    inc(line) { line.qty++; },
                    dec(line) { line.qty--; if (line.qty <= 0) this.remove(line); },
                    remove(line) { this.cart = this.cart.filter(l => l.id !== line.id); },
                    clear() { this.cart = []; },
                    get total() { return this.cart.reduce((s, l) => s + l.price * l.qty, 0); },
                    money(v) { return new Intl.NumberFormat('fr-FR').format(v) + ' MRU'; },
                };
            }
        </script>
    </div>
</x-app-layout>
