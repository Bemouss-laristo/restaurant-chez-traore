@php($isEdit = isset($item) && $item)

<div class="space-y-6">
    <div>
        <x-input-label for="name" value="Nom de l'article" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
            :value="old('name', $item->name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="unit" value="Unité de mesure" />
        <select id="unit" name="unit"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            @foreach ($units as $value => $label)
                <option value="{{ $value }}" @selected(old('unit', $item->unit->value ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('unit')" class="mt-2" />
    </div>

    @unless ($isEdit)
        <div>
            <x-input-label for="quantity" value="Quantité initiale (stock de départ)" />
            <x-text-input id="quantity" name="quantity" type="number" step="0.001" min="0" class="mt-1 block w-full"
                :value="old('quantity', 0)" required />
            <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
        </div>
    @endunless

    <div>
        <x-input-label for="alert_threshold" value="Seuil d'alerte" />
        <x-text-input id="alert_threshold" name="alert_threshold" type="number" step="0.001" min="0" class="mt-1 block w-full"
            :value="old('alert_threshold', $item->alert_threshold ?? 0)" required />
        <x-input-error :messages="$errors->get('alert_threshold')" class="mt-2" />
        <p class="mt-1 text-xs text-gray-500">En dessous de ce niveau, l'article est signalé « stock faible ».</p>
    </div>

    <div>
        <x-input-label for="unit_cost" value="Coût d'achat unitaire (MRU)" />
        <x-text-input id="unit_cost" name="unit_cost" type="number" step="0.01" min="0" class="mt-1 block w-full"
            :value="old('unit_cost', $item->unit_cost ?? 0)" required />
        <x-input-error :messages="$errors->get('unit_cost')" class="mt-2" />
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>{{ $isEdit ? 'Mettre à jour' : "Créer l'article" }}</x-primary-button>
        <a href="{{ route('stock-items.index') }}" class="text-sm text-gray-600 underline">Annuler</a>
    </div>
</div>
