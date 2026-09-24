<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="flex items-center gap-2 text-lg font-semibold leading-tight text-cocoa-900">
                <x-icon name="cart" class="h-6 w-6 text-brand-600" /> Nouvelle vente
            </h2>
            <a href="{{ route('sales.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:underline">
                <x-icon name="document" class="h-4 w-4" /> Historique
            </a>
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
                        <div class="relative w-full max-w-xs">
                            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-cocoa-400" />
                            <input type="search" x-model="search" placeholder="Rechercher un plat…" autocomplete="off"
                                class="w-full rounded-lg border-cocoa-200 pl-10 shadow-sm focus:border-brand-500 focus:ring-brand-500" />
                        </div>
                        <select x-model="category" class="rounded-lg border-cocoa-200 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">Toutes catégories</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                        <template x-for="p in filteredProducts" :key="p.id">
                            <button type="button" x-on:click="add(p)"
                                class="group relative overflow-hidden rounded-xl border border-cocoa-100 bg-white text-left shadow-soft transition-all duration-200 hover:-translate-y-1 hover:border-brand-300 hover:shadow-lift focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 active:scale-95"
                                :class="lastAdded === p.id && 'ring-2 ring-brand-500'">
                                <div class="overflow-hidden">
                                    <img :src="p.image" :alt="p.name" loading="lazy"
                                        x-on:error.once="$el.src = '{{ asset('images/placeholders/default.svg') }}'"
                                        class="h-24 w-full bg-cocoa-50 object-cover transition-transform duration-500 group-hover:scale-110" />
                                </div>
                                <span class="absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-full bg-white/90 text-brand-600 opacity-0 shadow transition-all duration-200 group-hover:opacity-100">
                                    <x-icon name="plus" class="h-4 w-4" />
                                </span>
                                <div class="p-2.5">
                                    <div class="text-sm font-semibold leading-tight text-cocoa-900" x-text="p.name"></div>
                                    <div class="mt-1 text-sm font-medium text-brand-600" x-text="money(p.price)"></div>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Panier --}}
                <div class="lg:col-span-1">
                    <div class="bg-white shadow sm:rounded-lg p-4 lg:sticky lg:top-4">
                        <h3 class="mb-3 flex items-center gap-2 font-semibold text-cocoa-900">
                            <x-icon name="cart" class="h-5 w-5 text-brand-600" /> Panier
                            <span x-show="count > 0" x-text="count"
                                class="inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-brand-600 px-2 text-xs font-bold text-white"
                                :class="bump && 'animate-pop'"></span>
                        </h3>

                        <div class="divide-y divide-gray-100">
                            <template x-for="line in cart" :key="line.id">
                                <div class="flex items-center gap-2 py-2">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900 truncate" x-text="line.name"></div>
                                        <div class="text-xs text-gray-500" x-text="money(line.price)"></div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button type="button" x-on:click="dec(line)" aria-label="Retirer un"
                                            class="h-8 w-8 rounded-lg bg-cocoa-50 font-semibold text-cocoa-700 transition hover:bg-cocoa-100 active:scale-90">−</button>
                                        <span class="w-6 text-center text-sm font-semibold" x-text="line.qty"></span>
                                        <button type="button" x-on:click="inc(line)" aria-label="Ajouter un"
                                            class="h-8 w-8 rounded-lg bg-brand-50 font-semibold text-brand-700 transition hover:bg-brand-100 active:scale-90">+</button>
                                    </div>
                                    <div class="w-20 text-right text-sm font-medium" x-text="money(line.price * line.qty)"></div>
                                </div>
                            </template>
                        </div>

                        <div x-show="cart.length === 0" class="py-8 text-center text-sm text-cocoa-500">
                            <x-icon name="cart" class="mx-auto mb-2 h-10 w-10 text-cocoa-200" />
                            Panier vide. Touche un produit pour l'ajouter.
                        </div>

                        <div class="flex justify-between items-center border-t border-gray-200 mt-3 pt-3">
                            <span class="font-semibold text-gray-800">Total</span>
                            <span class="text-2xl font-bold text-cocoa-900" x-text="money(total)"></span>
                        </div>

                        <form method="POST" action="{{ route('sales.store') }}" class="mt-4 space-y-3">
                            @csrf
                            <div>
                                <x-input-label for="payment_method" value="Mode de paiement" />
                                <select id="payment_method" name="payment_method" x-model="paymentMethod"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    @foreach (\App\Enums\PaymentMethod::salesOptions() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
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
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-3.5 text-base font-semibold text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50">
                                <x-icon name="check" class="h-5 w-5" /> Valider la vente
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
                    lastAdded: null,
                    bump: false,
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
                        if (line) { line.qty++; }
                        else { this.cart.push({ id: p.id, name: p.name, price: p.price, qty: 1 }); }
                        // Petit retour visuel : la tuile s'entoure, le compteur du panier saute.
                        this.lastAdded = p.id;
                        this.bump = false;
                        this.$nextTick(() => { this.bump = true; });
                        clearTimeout(this._flash);
                        this._flash = setTimeout(() => { this.lastAdded = null; this.bump = false; }, 450);
                    },
                    get count() { return this.cart.reduce((s, l) => s + l.qty, 0); },
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

    @include('partials.auto-print')
</x-app-layout>
