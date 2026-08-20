<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Modifier : {{ $product->name }}</h2>
    </x-slot>

    @php($initialRecipe = $product->stockItems->map(fn ($s) => [
        'stock_item_id' => $s->id,
        'quantity_needed' => (float) $s->pivot->quantity_needed,
    ])->values())

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif

            {{-- Fiche produit --}}
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    @include('products._form', ['product' => $product])
                </form>
            </div>

            {{-- Recette --}}
            <div class="bg-white p-6 shadow sm:rounded-lg" x-data="recipeEditor(@js($initialRecipe))">
                <h3 class="font-medium text-gray-800 mb-1">Recette (ingrédients consommés)</h3>
                <p class="text-xs text-gray-500 mb-4">
                    Ce que la préparation d'une unité de ce produit retire du stock. Le coût estimé du
                    produit sera recalculé automatiquement à partir du coût des ingrédients.
                </p>

                <form method="POST" action="{{ route('products.recipe', $product) }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-2">
                        <template x-for="(row, index) in rows" :key="index">
                            <div class="flex flex-wrap items-center gap-2">
                                <select :name="`items[${index}][stock_item_id]`" x-model="row.stock_item_id"
                                    class="border-gray-300 rounded-md shadow-sm w-full sm:w-64">
                                    <option value="">— Ingrédient —</option>
                                    @foreach ($stockItems as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->unit->value }})</option>
                                    @endforeach
                                </select>
                                <input type="number" step="0.001" min="0" :name="`items[${index}][quantity_needed]`"
                                    x-model="row.quantity_needed" placeholder="Quantité"
                                    class="border-gray-300 rounded-md shadow-sm w-32" />
                                <button type="button" x-on:click="remove(index)"
                                    class="text-red-600 hover:underline text-sm">Retirer</button>
                            </div>
                        </template>
                    </div>

                    <div x-show="rows.length === 0" class="text-sm text-gray-500 py-2">
                        Aucun ingrédient pour l'instant. Ajoutes-en pour lier ce plat au stock.
                    </div>

                    <x-input-error :messages="$errors->get('items.*.stock_item_id')" class="mt-2" />
                    <x-input-error :messages="$errors->get('items.*.quantity_needed')" class="mt-2" />

                    <div class="mt-4 flex items-center gap-3">
                        <button type="button" x-on:click="add()"
                            class="px-3 py-2 bg-gray-100 text-gray-700 text-sm rounded-md hover:bg-gray-200">
                            + Ajouter un ingrédient
                        </button>
                        <x-primary-button>Enregistrer la recette</x-primary-button>
                    </div>
                </form>
            </div>

            {{-- Archivage --}}
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <form method="POST" action="{{ route('products.destroy', $product) }}"
                    onsubmit="return confirm('Archiver ce produit ?');">
                    @csrf
                    @method('DELETE')
                    <button class="text-red-600 hover:underline text-sm">Archiver ce produit</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function recipeEditor(initial) {
            return {
                rows: Array.isArray(initial)
                    ? initial.map(r => ({ stock_item_id: String(r.stock_item_id), quantity_needed: r.quantity_needed }))
                    : [],
                add() { this.rows.push({ stock_item_id: '', quantity_needed: '' }); },
                remove(index) { this.rows.splice(index, 1); },
            };
        }
    </script>
</x-app-layout>
