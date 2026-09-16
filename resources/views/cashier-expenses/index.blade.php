<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dépenses du jour</h2>
            <a href="{{ route('caisse.index') }}" class="text-sm text-indigo-600 hover:underline">Voir la caisse</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">{{ session('error') }}</div>
            @endif

            @if ($session === null)
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-md">
                    ⚠ Aucune caisse ouverte. Ouvre la caisse pour pouvoir noter une dépense.
                    <a href="{{ route('caisse.index') }}" class="underline font-medium">Ouvrir la caisse</a>
                </div>
            @endif

            {{-- Formulaire --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-1">Noter une dépense</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Pour l'argent pris dans la caisse (espèces). Le montant est retiré de la caisse théorique.
                </p>

                @php
                    $oldItems = old('items');
                    $initialItems = is_array($oldItems) && count($oldItems) ? array_values($oldItems) : [['label' => '', 'price' => '']];
                @endphp
                <form method="POST" action="{{ route('cashier-expenses.store') }}" class="space-y-4"
                    x-data="expenseList(@js($initialItems))">
                    @csrf
                    <div>
                        <x-input-label for="expense_category" value="Catégorie" />
                        <select id="expense_category" name="expense_category"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach ($categories as $value => $label)
                                <option value="{{ $value }}" @selected(old('expense_category', 'achat_marchandises') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('expense_category')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label value="Articles achetés" />
                        <p class="text-xs text-gray-500">Une ligne par article. Appuie sur Entrée dans le prix pour passer à la ligne suivante.</p>

                        <div class="mt-2 space-y-2">
                            <template x-for="(row, idx) in rows" :key="row.key">
                                <div class="flex items-center gap-2">
                                    <input type="text" :name="`items[${idx}][label]`" x-model="row.label"
                                        placeholder="Article (ex : pain)" maxlength="100"
                                        class="flex-1 min-w-0 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                    <input type="number" :name="`items[${idx}][price]`" x-model="row.price"
                                        placeholder="Prix" min="1" step="1" inputmode="numeric"
                                        x-on:keydown.enter.prevent="addRow()"
                                        class="w-28 text-right border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                    <button type="button" x-on:click="removeRow(idx)" title="Supprimer la ligne"
                                        class="w-8 h-8 rounded bg-gray-100 hover:bg-gray-200 text-gray-700">×</button>
                                </div>
                            </template>
                        </div>

                        <button type="button" x-on:click="addRow()" class="mt-2 text-sm text-indigo-600 hover:underline">+ Ajouter un article</button>

                        @if ($errors->has('items') || $errors->has('items.*'))
                            <p class="mt-2 text-sm text-red-600">{{ $errors->first('items') ?: collect($errors->get('items.*'))->flatten()->first() }}</p>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-3">
                        <div class="text-lg">Total : <span class="font-bold text-red-700" x-text="money(total)"></span></div>
                        <button type="submit" @disabled($session === null) x-bind:disabled="{{ $session === null ? 'true' : 'false' }} || total <= 0"
                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            Enregistrer la dépense
                        </button>
                    </div>
                </form>

                <script>
                    function expenseList(initial) {
                        let next = 0;
                        const make = (r) => ({ key: next++, label: r.label ?? '', price: r.price ?? '' });
                        return {
                            rows: initial.map(make),
                            addRow() {
                                this.rows.push(make({}));
                                this.$nextTick(() => {
                                    const inputs = this.$el.querySelectorAll('input[type=text]');
                                    inputs[inputs.length - 1]?.focus();
                                });
                            },
                            removeRow(idx) {
                                this.rows.splice(idx, 1);
                                if (this.rows.length === 0) this.rows.push(make({}));
                            },
                            get total() {
                                return this.rows.reduce((s, r) => s + (parseFloat(r.price) || 0), 0);
                            },
                            money(v) { return new Intl.NumberFormat('fr-FR').format(v) + ' MRU'; },
                        };
                    }
                </script>
            </div>

            {{-- Liste du jour --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <h3 class="font-medium text-gray-800">Mes dépenses d'aujourd'hui</h3>
                    <div class="text-sm">
                        Total : <span class="font-semibold text-red-700">@mru($total)</span>
                        @if ($expected !== null)
                            <span class="ms-3 text-gray-500">Caisse théorique : <span class="font-semibold text-indigo-700">@mru($expected)</span></span>
                        @endif
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-3 py-2">Heure</th>
                                <th class="px-3 py-2">Catégorie</th>
                                <th class="px-3 py-2">Articles</th>
                                <th class="px-3 py-2 text-right">Montant</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($expenses as $expense)
                                <tr>
                                    <td class="px-3 py-2 text-gray-600">{{ $expense->created_at->format('H:i') }}</td>
                                    <td class="px-3 py-2">{{ $expense->expense_category->label() }}</td>
                                    <td class="px-3 py-2 text-gray-700">{!! nl2br(e($expense->description)) !!}</td>
                                    <td class="px-3 py-2 text-right font-medium">@mru($expense->amount)</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-3 py-6 text-center text-gray-500">Aucune dépense notée aujourd'hui.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-gray-500">Une erreur ? Demande au gérant de la corriger.</p>
            </div>
        </div>
    </div>
</x-app-layout>
