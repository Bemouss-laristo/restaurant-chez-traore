@php
    $isEdit = isset($expense) && $expense;
    $oldItems = old('items');
    $initialItems = is_array($oldItems) && count($oldItems)
        ? array_values($oldItems)
        : ($isEdit ? \App\Http\Requests\ExpenseItems::parse($expense->description)->all() : [['label' => '', 'price' => '']]);
    $initialItems = $initialItems ?: [['label' => '', 'price' => '']];
@endphp

<div class="space-y-6" x-data="expenseList(@js($initialItems))">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <x-input-label for="expense_category" value="Catégorie" />
            <select id="expense_category" name="expense_category"
                class="mt-1 block w-full border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm">
                @foreach ($categories as $value => $label)
                    <option value="{{ $value }}"
                        @selected(old('expense_category', $expense->expense_category->value ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('expense_category')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="spent_at" value="Date" />
            <x-text-input id="spent_at" name="spent_at" type="date" class="mt-1 block w-full"
                :value="old('spent_at', isset($expense) ? $expense->spent_at->format('Y-m-d') : \App\Support\BusinessDay::today())" required />
            <x-input-error :messages="$errors->get('spent_at')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="payment_method" value="Mode de paiement" />
            <select id="payment_method" name="payment_method"
                class="mt-1 block w-full border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm">
                @foreach ($paymentMethods as $value => $label)
                    <option value="{{ $value }}"
                        @selected(old('payment_method', $expense->payment_method->value ?? 'especes') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">Une dépense en espèces sort de la caisse ouverte.</p>
            <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label value="Articles / détail de la dépense" />
        <p class="text-xs text-gray-500">Une ligne par article, avec son prix. Le total se calcule tout seul.</p>

        <div class="mt-2 space-y-2">
            <template x-for="(row, idx) in rows" :key="row.key">
                <div class="flex items-center gap-2">
                    <input type="text" :name="`items[${idx}][label]`" x-model="row.label"
                        placeholder="Article (ex : sac de charbon)" maxlength="100"
                        class="flex-1 min-w-0 border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm" />
                    <input type="number" :name="`items[${idx}][price]`" x-model="row.price"
                        placeholder="Prix" min="1" step="1" inputmode="numeric"
                        x-on:keydown.enter.prevent="addRow()"
                        class="w-28 text-right border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm" />
                    <button type="button" x-on:click="removeRow(idx)" title="Supprimer la ligne"
                        class="w-8 h-8 rounded bg-gray-100 hover:bg-gray-200 text-gray-700">×</button>
                </div>
            </template>
        </div>

        <button type="button" x-on:click="addRow()" class="mt-2 text-sm text-brand-600 hover:underline">+ Ajouter un article</button>

        @if ($errors->has('items') || $errors->has('items.*'))
            <p class="mt-2 text-sm text-red-600">{{ $errors->first('items') ?: collect($errors->get('items.*'))->flatten()->first() }}</p>
        @endif
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-3">
        <div class="text-lg">Total : <span class="font-bold text-red-700" x-text="money(total)"></span></div>
        <div class="flex items-center gap-4">
            <x-primary-button>{{ $isEdit ? 'Mettre à jour' : 'Enregistrer la dépense' }}</x-primary-button>
            <a href="{{ route('expenses.index') }}" class="text-sm text-gray-600 underline">Annuler</a>
        </div>
    </div>
</div>

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
            get total() { return this.rows.reduce((s, r) => s + (parseFloat(r.price) || 0), 0); },
            money(v) { return new Intl.NumberFormat('fr-FR').format(v) + ' MRU'; },
        };
    }
</script>
