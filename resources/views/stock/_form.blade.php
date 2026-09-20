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
        <x-input-label for="supplier_id" value="Fournisseur habituel (facultatif)" />
        <select id="supplier_id" name="supplier_id"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">— Aucun —</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $item->supplier_id ?? null) == $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">Il sera pré-sélectionné à la saisie d'un achat de cet article.</p>
        <x-input-error :messages="$errors->get('supplier_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="default_payment_method" value="Mode d'achat habituel" />
        <select id="default_payment_method" name="default_payment_method"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">— Demander à chaque achat —</option>
            @foreach ($paymentMethods as $value => $label)
                <option value="{{ $value }}" @selected(old('default_payment_method', $item->default_payment_method->value ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">
            Choisis <strong>À crédit</strong> pour un article pris chez le fournisseur et payé en fin de mois
            (pain arabe) : chaque saisie fera automatiquement monter sa dette.
        </p>
        <x-input-error :messages="$errors->get('default_payment_method')" class="mt-2" />
    </div>

    <div class="border border-gray-200 rounded-md p-4">
        <p class="text-sm font-medium text-gray-700">Prix convenu avec le fournisseur (facultatif)</p>
        <p class="mt-1 text-xs text-gray-500">
            Quand le prix est fixé d'avance (40 MRU le paquet de pain, 220 MRU le kilo de viande),
            un bloc <strong>« Prise du jour »</strong> apparaît dans le menu Achats : il suffit alors
            de saisir la quantité prise, le montant se calcule tout seul.
        </p>
        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="agreed_unit_price" value="Prix convenu par unité (MRU)" />
                <x-text-input id="agreed_unit_price" name="agreed_unit_price" type="number" step="0.01" min="0" class="mt-1 block w-full"
                    :value="old('agreed_unit_price', ($item->agreed_unit_price ?? null) > 0 ? $item->agreed_unit_price : '')" placeholder="Ex : 40" />
                <x-input-error :messages="$errors->get('agreed_unit_price')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="daily_quantity" value="Quantité habituelle par jour (facultatif)" />
                <x-text-input id="daily_quantity" name="daily_quantity" type="number" step="0.001" min="0" class="mt-1 block w-full"
                    :value="old('daily_quantity', ($item->daily_quantity ?? null) > 0 ? $item->daily_quantity : '')" placeholder="Ex : 2" />
                <x-input-error :messages="$errors->get('daily_quantity')" class="mt-2" />
                <p class="mt-1 text-xs text-gray-500">Pré-remplit la prise du jour. Laisse vide si la quantité change tous les jours.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="pack_label" value="Conditionnement d'achat (facultatif)" />
            <x-text-input id="pack_label" name="pack_label" type="text" class="mt-1 block w-full"
                :value="old('pack_label', $item->pack_label ?? '')" placeholder="Ex : Carton (6 sachets × 18)" maxlength="60" />
            <x-input-error :messages="$errors->get('pack_label')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="pack_quantity" value="Unités par conditionnement" />
            <x-text-input id="pack_quantity" name="pack_quantity" type="number" step="0.001" min="0" class="mt-1 block w-full"
                :value="old('pack_quantity', $item->pack_quantity ?? '')" placeholder="Ex : 108" />
            <x-input-error :messages="$errors->get('pack_quantity')" class="mt-2" />
            <p class="mt-1 text-xs text-gray-500">Permet de saisir « 2 cartons » à l'achat : la conversion est automatique.</p>
        </div>
    </div>

    <div>
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="is_key" value="1" @checked(old('is_key', $item->is_key ?? false))
                class="rounded border-gray-300 text-indigo-600" />
            <span class="text-sm font-medium text-gray-700">Article clé — à compter chaque soir</span>
        </label>
        <p class="mt-1 text-xs text-gray-500">Pain, poulet, boissons… Ce sont eux qui partent le plus vite et se perdent le plus.</p>
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
