@php($isEdit = isset($expense) && $expense)

<div class="space-y-6">
    <div>
        <x-input-label for="expense_category" value="Catégorie" />
        <select id="expense_category" name="expense_category"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            @foreach ($categories as $value => $label)
                <option value="{{ $value }}"
                    @selected(old('expense_category', $expense->expense_category->value ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('expense_category')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="amount" value="Montant (MRU)" />
            <x-text-input id="amount" name="amount" type="number" step="0.01" min="0" class="mt-1 block w-full"
                :value="old('amount', $expense->amount ?? '')" required autofocus />
            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="spent_at" value="Date" />
            <x-text-input id="spent_at" name="spent_at" type="date" class="mt-1 block w-full"
                :value="old('spent_at', isset($expense) ? $expense->spent_at->format('Y-m-d') : date('Y-m-d'))" required />
            <x-input-error :messages="$errors->get('spent_at')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="payment_method" value="Mode de paiement" />
        <select id="payment_method" name="payment_method"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            @foreach ($paymentMethods as $value => $label)
                <option value="{{ $value }}"
                    @selected(old('payment_method', $expense->payment_method->value ?? 'especes') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">Une dépense en espèces sort de la caisse ouverte.</p>
        <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="description" value="Description (facultatif)" />
        <textarea id="description" name="description" rows="2"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $expense->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>{{ $isEdit ? 'Mettre à jour' : 'Enregistrer la dépense' }}</x-primary-button>
        <a href="{{ route('expenses.index') }}" class="text-sm text-gray-600 underline">Annuler</a>
    </div>
</div>
