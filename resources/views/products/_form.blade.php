@php($isEdit = isset($product) && $product)

<div class="space-y-6">
    <div>
        <x-input-label for="product_category_id" value="Catégorie" />
        <select id="product_category_id" name="product_category_id"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">— Choisir —</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}"
                    @selected((int) old('product_category_id', $product->product_category_id ?? 0) === $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('product_category_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="name" value="Nom du produit" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
            :value="old('name', $product->name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <x-input-label for="sale_price" value="Prix de vente (MRU)" />
            <x-text-input id="sale_price" name="sale_price" type="number" step="0.01" min="0" class="mt-1 block w-full"
                :value="old('sale_price', $product->sale_price ?? '')" required />
            <x-input-error :messages="$errors->get('sale_price')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="estimated_cost" value="Coût estimé (MRU)" />
            <x-text-input id="estimated_cost" name="estimated_cost" type="number" step="0.01" min="0" class="mt-1 block w-full"
                :value="old('estimated_cost', $product->estimated_cost ?? 0)" />
            <x-input-error :messages="$errors->get('estimated_cost')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="description" value="Description (facultatif)" />
        <textarea id="description" name="description" rows="2"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $product->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="image" value="Photo du produit" />
        <div class="mt-1 flex items-center gap-4">
            <img src="{{ $isEdit ? $product->imageUrl() : asset('images/placeholders/default.svg') }}"
                alt="Aperçu" class="h-20 w-28 object-cover rounded-md border border-gray-200" />
            <input id="image" name="image" type="file" accept="image/*"
                class="block text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
        </div>
        <p class="mt-1 text-xs text-gray-500">JPG, PNG ou WebP, 2 Mo max. Sans photo, une vignette de catégorie s'affiche.</p>
        <x-input-error :messages="$errors->get('image')" class="mt-2" />
    </div>

    <label class="flex items-center">
        <input type="checkbox" name="is_active" value="1"
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
            @checked(old('is_active', $product->is_active ?? true)) />
        <span class="ms-2 text-sm text-gray-600">Produit actif (visible à la vente)</span>
    </label>

    <div class="flex items-center gap-4">
        <x-primary-button>{{ $isEdit ? 'Mettre à jour' : 'Créer le produit' }}</x-primary-button>
        <a href="{{ route('products.index') }}" class="text-sm text-gray-600 underline">Annuler</a>
    </div>
</div>
