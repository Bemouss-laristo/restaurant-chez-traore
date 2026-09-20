<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Achat de marchandises</h2>
            <a href="{{ route('reports.material') }}" class="text-sm text-indigo-600 hover:underline">Contrôle matière →</a>
        </div>
    </x-slot>

    @php
        $itemsData = $items->map(fn ($i) => [
            'id' => $i->id,
            'name' => $i->name,
            'unit' => $i->unit->value,
            'pack_label' => $i->pack_label,
            'pack_quantity' => (float) $i->pack_quantity,
            'unit_cost' => (float) $i->unit_cost,
            'supplier_id' => $i->supplier_id,
            'supplier_name' => $i->supplier?->name,
            'payment_method' => $i->default_payment_method?->value,
        ])->values();
    @endphp

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">{{ $errors->first() }}</div>
            @endif

            @if ($incomplete->isNotEmpty())
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-md text-sm">
                    <strong>Paramétrage incomplet.</strong>
                    Ces articles n'apparaissent pas dans la prise du jour, il leur manque une information :
                    <div class="mt-2 space-y-1">
                        @foreach ($incomplete as $item)
                            <div>
                                <a href="{{ route('stock-items.edit', $item) }}" class="font-medium underline">{{ $item->name }}</a>
                                —
                                @if (! $item->supplier)
                                    il faut choisir son <strong>fournisseur habituel</strong>.
                                @else
                                    il faut renseigner son <strong>prix convenu par unité</strong>.
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($agreed->isNotEmpty())
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-800">Prise du jour</h3>
                    <p class="text-sm text-gray-500 mb-3">
                        Prix déjà convenu avec le fournisseur : saisis seulement la quantité prise aujourd'hui.
                        Le montant se calcule tout seul et s'ajoute à ce que tu lui devras en fin de mois.
                    </p>
                    <div class="space-y-3">
                        @foreach ($agreed as $item)
                            @php($taken = $item->takenToday())
                            <div class="border border-gray-200 rounded-md p-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="text-sm">
                                        <span class="font-medium text-gray-800">{{ $item->name }}</span>
                                        <span class="text-gray-600">— @mru($item->agreed_unit_price) le {{ $item->unit->value }}</span>
                                        @if ($item->supplier)
                                            <span class="text-gray-600">— {{ $item->supplier->name }}</span>
                                        @endif
                                        @if (($item->default_payment_method?->value ?? null) === 'credit')
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">à crédit</span>
                                        @endif
                                    </div>
                                    @if ($taken > 0)
                                        <span class="text-xs text-gray-500">
                                            Déjà pris aujourd'hui : {{ rtrim(rtrim(number_format($taken, 3, ',', ' '), '0'), ',') }} {{ $item->unit->value }}
                                        </span>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('purchases.quick', $item) }}"
                                    class="mt-2 flex flex-wrap items-center gap-2"
                                    x-data="{ q: '{{ (float) $item->daily_quantity > 0 ? rtrim(rtrim(number_format((float) $item->daily_quantity, 3, '.', ''), '0'), '.') : '' }}', p: {{ (float) $item->agreed_unit_price }} }">
                                    @csrf
                                    <input type="number" name="quantity" x-model="q" step="0.001" min="0"
                                        placeholder="Quantité en {{ $item->unit->value }}" required
                                        class="w-32 text-right border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                    <span class="text-sm text-gray-600" x-show="q > 0"
                                        x-text="'= ' + new Intl.NumberFormat('fr-FR').format(Math.round(q * p)) + ' MRU'"></span>
                                    <button class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                                        Enregistrer
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs text-gray-500">
                        Tu peux saisir plusieurs fois dans la journée : chaque prise s'ajoute.
                        Le détail jour par jour est dans <a href="{{ route('suppliers.index') }}" class="text-indigo-600 hover:underline">le relevé du fournisseur</a>.
                    </p>
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6" x-data="{ open: {{ $agreed->isEmpty() ? 'true' : 'false' }} }">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="font-medium text-gray-800">Ajouter un article à la prise du jour</h3>
                    <button type="button" x-on:click="open = ! open" class="text-sm text-indigo-600 hover:underline"
                        x-text="open ? 'Masquer' : 'Afficher'"></button>
                </div>
                <p class="text-sm text-gray-500">
                    Rattache un article à un fournisseur avec un prix convenu. Il apparaîtra alors ci-dessus,
                    et son compte se remplira tout seul à chaque prise.
                </p>

                <form method="POST" action="{{ route('purchases.configure') }}" class="mt-4 space-y-4" x-show="open" style="display: none;">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="cfg_item" value="Article" />
                            <select id="cfg_item" name="stock_item_id" required
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">— Choisir —</option>
                                @foreach ($configurable as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->unit->value }})</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">
                                L'article n'existe pas ?
                                <a href="{{ route('stock-items.create') }}" class="text-indigo-600 hover:underline">Créer un article</a>
                            </p>
                        </div>
                        <div>
                            <x-input-label for="cfg_supplier" value="Fournisseur existant" />
                            <select id="cfg_supplier" name="supplier_id"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">— Aucun —</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="cfg_new" value="… ou nouveau fournisseur" />
                            <x-text-input id="cfg_new" name="new_supplier" type="text" class="mt-1 block w-full"
                                placeholder="Nom du fournisseur" maxlength="120" />
                            <x-text-input name="new_supplier_phone" type="text" class="mt-2 block w-full"
                                placeholder="Téléphone (facultatif)" maxlength="30" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="cfg_price" value="Prix convenu par unité (MRU)" />
                            <x-text-input id="cfg_price" name="agreed_unit_price" type="number" step="0.01" min="0" class="mt-1 block w-full"
                                placeholder="Ex : 40" required />
                        </div>
                        <div>
                            <x-input-label for="cfg_daily" value="Quantité habituelle par jour" />
                            <x-text-input id="cfg_daily" name="daily_quantity" type="number" step="0.001" min="0" class="mt-1 block w-full"
                                placeholder="Facultatif" />
                            <p class="mt-1 text-xs text-gray-500">Laisse vide si la quantité change chaque jour.</p>
                        </div>
                        <div>
                            <x-input-label for="cfg_method" value="Règlement habituel" />
                            <select id="cfg_method" name="payment_method"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach ($paymentMethods as $value => $label)
                                    <option value="{{ $value }}" @selected($value === 'credit')>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">« À crédit » = payé en fin de mois.</p>
                        </div>
                    </div>

                    <x-primary-button>Ajouter à la prise du jour</x-primary-button>
                </form>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6" x-data="purchaseForm(@js($itemsData))">
                <p class="text-sm text-gray-500 mb-4">
                    Une seule saisie : la marchandise entre en stock <strong>et</strong> la dépense est enregistrée.
                    Saisis en carton / sachet quand c'est possible : la conversion en unités est automatique.
                </p>

                <form method="POST" action="{{ route('purchases.store') }}" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="supplier_id" value="Fournisseur" />
                            <select id="supplier_id" name="supplier_id" x-model="supplierId"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">— Achat ponctuel (sans compte) —</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('supplier_id')" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500"><a href="{{ route('suppliers.index') }}" class="text-indigo-600 hover:underline">Gérer les fournisseurs</a></p>
                        </div>
                        <div>
                            <x-input-label for="spent_at" value="Date" />
                            <x-text-input id="spent_at" name="spent_at" type="date" class="mt-1 block w-full"
                                :value="old('spent_at', $today)" required />
                        </div>
                        <div>
                            <x-input-label for="payment_method" value="Payé par" />
                            <select id="payment_method" name="payment_method" x-model="paymentMethod"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach ($paymentMethods as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">« Espèces » sort de la caisse ouverte. « À crédit » n'enlève rien maintenant : la dette du fournisseur monte et tu règles en fin de mois.</p>
                            <p class="mt-1 text-xs text-gray-600" x-show="autoNote" x-text="autoNote"></p>
                        </div>
                    </div>

                    <div>
                        <x-input-label value="Articles achetés" />
                        <div class="mt-2 space-y-3">
                            <template x-for="(row, idx) in rows" :key="row.key">
                                <div class="border border-gray-200 rounded-md p-3">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <select :name="`lines[${idx}][stock_item_id]`" x-model="row.itemId" x-on:change="onItemChange(row)"
                                                class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                                <option value="">— Choisir un article —</option>
                                                <template x-for="item in items" :key="item.id">
                                                    <option :value="item.id" x-text="item.name"></option>
                                                </template>
                                            </select>
                                            <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600" x-show="packOf(row)">
                                                <input type="checkbox" :name="`lines[${idx}][pack]`" value="1" x-model="row.pack"
                                                    class="rounded border-gray-300 text-indigo-600" />
                                                <span x-text="'Saisir en ' + (packOf(row)?.pack_label || 'carton')"></span>
                                            </label>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <input type="number" :name="`lines[${idx}][quantity]`" x-model="row.quantity"
                                                    step="0.001" min="0" placeholder="Quantité"
                                                    class="block w-full text-right border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                                <div class="mt-1 text-xs text-gray-500" x-text="unitsLabel(row)"></div>
                                            </div>
                                            <div>
                                                <input type="number" :name="`lines[${idx}][total_price]`" x-model="row.price"
                                                    step="1" min="0" placeholder="Prix payé (MRU)"
                                                    class="block w-full text-right border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                                <div class="mt-1 text-xs text-gray-500" x-text="unitCostLabel(row)"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-right">
                                        <button type="button" x-on:click="removeRow(idx)" class="text-xs text-red-600 hover:underline">Retirer cette ligne</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <button type="button" x-on:click="addRow()" class="mt-2 text-sm text-indigo-600 hover:underline">+ Ajouter un article</button>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-3">
                        <div class="text-lg">Total de l'achat : <span class="font-bold text-red-700" x-text="money(total)"></span></div>
                        <button type="submit" x-bind:disabled="total <= 0"
                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            Enregistrer l'achat
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6" x-data="{ editing: null }">
                <h3 class="font-medium text-gray-800 mb-1">Derniers achats</h3>
                <p class="text-sm text-gray-500 mb-3">
                    Une erreur de saisie se corrige ici : le stock et le compte du fournisseur suivent automatiquement.
                </p>
                <div class="divide-y divide-gray-100">
                    @forelse ($recent as $purchase)
                        @php($line = $purchase->stockMovements->count() === 1 ? $purchase->stockMovements->first() : null)
                        <div class="py-3 text-sm">
                            <div class="flex flex-wrap justify-between gap-2">
                                <span class="text-gray-600">
                                    {{ $purchase->spent_at->format('d/m/Y') }} — {{ $purchase->user->name ?? '—' }}
                                    @if ($purchase->supplier) — <span class="font-medium text-gray-800">{{ $purchase->supplier->name }}</span> @endif
                                    @if ($purchase->isCredit()) <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">à crédit</span> @endif
                                </span>
                                <span class="font-medium">@mru($purchase->amount)</span>
                            </div>
                            <div class="text-gray-700">{!! nl2br(e($purchase->description)) !!}</div>

                            <div class="mt-1 flex flex-wrap items-center gap-3">
                                @if ($line && $line->stockItem)
                                    <button type="button" x-on:click="editing = (editing === {{ $purchase->id }} ? null : {{ $purchase->id }})"
                                        class="text-xs text-indigo-600 hover:underline">Corriger la quantité</button>
                                @endif
                                <form method="POST" action="{{ route('purchases.destroy', $purchase) }}"
                                    onsubmit="return confirm('Supprimer cette saisie ? Le stock et le compte du fournisseur reviendront comme avant.');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs text-red-600 hover:underline">Supprimer</button>
                                </form>
                            </div>

                            @if ($line && $line->stockItem)
                                <form method="POST" action="{{ route('purchases.update', $purchase) }}"
                                    class="mt-2 flex flex-wrap items-center gap-2"
                                    x-show="editing === {{ $purchase->id }}" style="display: none;">
                                    @csrf
                                    @method('PUT')
                                    <span class="text-xs text-gray-500">{{ $line->stockItem->name }} — quantité réelle :</span>
                                    <input type="number" name="quantity" step="0.001" min="0" required
                                        value="{{ rtrim(rtrim(number_format((float) $line->quantity, 3, '.', ''), '0'), '.') }}"
                                        class="w-32 text-right border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                    <span class="text-xs text-gray-500">{{ $line->stockItem->unit->value }}</span>
                                    <button class="px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                                        Enregistrer la correction
                                    </button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Aucun achat enregistré pour l'instant.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        function purchaseForm(items) {
            let next = 0;
            const make = () => ({ key: next++, itemId: '', pack: false, quantity: '', price: '' });
            return {
                items: items,
                rows: [make()],
                supplierId: @js(old('supplier_id', '')),
                paymentMethod: @js(old('payment_method', 'especes')),
                autoNote: '',
                addRow() { this.rows.push(make()); },
                removeRow(idx) { this.rows.splice(idx, 1); if (this.rows.length === 0) this.rows.push(make()); },
                itemOf(row) { return this.items.find(i => String(i.id) === String(row.itemId)); },
                packOf(row) { const i = this.itemOf(row); return i && i.pack_quantity > 0 ? i : null; },
                onItemChange(row) {
                    if (!this.packOf(row)) { row.pack = false; }
                    // L'article porte ses habitudes d'achat : fournisseur et mode de règlement.
                    // C'est ce qui fait qu'une entrée de pain arabe devient toujours une dette
                    // chez Lassana Camara, sans que personne ait à y penser.
                    const item = this.itemOf(row);
                    if (!item) { return; }
                    const notes = [];
                    if (item.supplier_id && !this.supplierId) {
                        this.supplierId = String(item.supplier_id);
                        notes.push('fournisseur : ' + (item.supplier_name || ''));
                    }
                    if (item.payment_method) {
                        this.paymentMethod = item.payment_method;
                        notes.push('règlement habituel de ' + item.name);
                    }
                    this.autoNote = notes.length ? 'Pré-rempli d\'après la fiche article (' + notes.join(', ') + '). Modifiable.' : '';
                },
                units(row) {
                    const qty = parseFloat(row.quantity) || 0;
                    const pack = this.packOf(row);
                    return row.pack && pack ? qty * pack.pack_quantity : qty;
                },
                unitsLabel(row) {
                    const item = this.itemOf(row);
                    if (!item) { return ''; }
                    return '= ' + this.units(row).toLocaleString('fr-FR') + ' ' + item.unit;
                },
                unitCostLabel(row) {
                    const units = this.units(row);
                    const price = parseFloat(row.price) || 0;
                    if (!units || !price) { return ''; }
                    const item = this.itemOf(row);
                    return 'soit ' + (price / units).toFixed(2).replace('.', ',') + ' MRU / ' + (item ? item.unit : 'unité');
                },
                get total() { return this.rows.reduce((s, r) => s + (parseFloat(r.price) || 0), 0); },
                money(v) { return new Intl.NumberFormat('fr-FR').format(v) + ' MRU'; },
            };
        }
    </script>
</x-app-layout>
