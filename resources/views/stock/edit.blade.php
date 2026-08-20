<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Article : {{ $item->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">{{ session('error') }}</div>
            @endif

            {{-- Quantité actuelle --}}
            <div class="bg-white p-6 shadow sm:rounded-lg flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">Quantité en stock</div>
                    <div class="text-2xl font-semibold {{ $item->isLow() ? 'text-red-600' : 'text-gray-900' }}">
                        {{ (float) $item->quantity }} {{ $item->unit->value }}
                    </div>
                </div>
                @if ($item->isLow())
                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Stock faible</span>
                @endif
            </div>

            {{-- Fiche article --}}
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <h3 class="font-medium text-gray-800 mb-4">Fiche de l'article</h3>
                <form method="POST" action="{{ route('stock-items.update', $item) }}">
                    @csrf
                    @method('PATCH')
                    @include('stock._form', ['item' => $item])
                </form>
            </div>

            {{-- Enregistrer un mouvement --}}
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <h3 class="font-medium text-gray-800 mb-4">Enregistrer un mouvement</h3>
                <form method="POST" action="{{ route('stock-items.movement', $item) }}"
                    class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    @csrf
                    <div>
                        <x-input-label for="action" value="Type" />
                        <select id="action" name="action"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="purchase">Entrée (achat)</option>
                            <option value="waste">Sortie (perte)</option>
                            <option value="adjustment">Ajustement (inventaire)</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="quantity_mv" value="Quantité" />
                        <x-text-input id="quantity_mv" name="quantity" type="number" step="0.001" min="0"
                            class="mt-1 block w-full" :value="old('quantity')" required />
                        <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                    </div>
                    <div>
                        <x-primary-button>Enregistrer</x-primary-button>
                    </div>
                    <div class="sm:col-span-3">
                        <x-input-label for="note" value="Note (facultatif)" />
                        <x-text-input id="note" name="note" type="text" class="mt-1 block w-full" :value="old('note')" />
                    </div>
                </form>
                <p class="mt-2 text-xs text-gray-500">
                    Pour un ajustement, saisis la quantité réellement comptée : le stock sera fixé à cette valeur.
                </p>
            </div>

            {{-- Historique récent --}}
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <h3 class="font-medium text-gray-800 mb-4">20 derniers mouvements</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-3 py-2">Date</th>
                                <th class="px-3 py-2">Type</th>
                                <th class="px-3 py-2">Raison</th>
                                <th class="px-3 py-2 text-right">Quantité</th>
                                <th class="px-3 py-2">Par</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($movements as $mv)
                                <tr>
                                    <td class="px-3 py-2 text-gray-600">{{ $mv->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2">{{ $mv->type->label() }}</td>
                                    <td class="px-3 py-2">{{ $mv->reason->label() }}</td>
                                    <td class="px-3 py-2 text-right font-medium {{ $mv->type->sign() > 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $mv->type->sign() > 0 ? '+' : '−' }}{{ (float) $mv->quantity }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-600">{{ $mv->user?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-3 py-4 text-center text-gray-500">Aucun mouvement.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Suppression --}}
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <form method="POST" action="{{ route('stock-items.destroy', $item) }}"
                    onsubmit="return confirm('Supprimer cet article ?');">
                    @csrf
                    @method('DELETE')
                    <button class="text-red-600 hover:underline text-sm">Supprimer cet article</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
