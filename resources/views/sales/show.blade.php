<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Vente {{ $sale->sale_number }}</h2>
            <div class="flex items-center gap-4">
                <a href="{{ route('sales.receipt', $sale) }}" target="_blank"
                   class="text-sm font-semibold px-3 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">🖨️ Imprimer le ticket</a>
                <a href="{{ route('sales.index') }}" class="text-sm text-indigo-600 hover:underline">← Retour à l'historique</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Infos générales --}}
            <div class="bg-white shadow sm:rounded-lg p-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <div class="text-xs text-gray-500">Date</div>
                    <div class="font-medium text-gray-800">{{ $sale->sold_at->format('d/m/Y H:i') }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Caissier</div>
                    <div class="font-medium text-gray-800">{{ $sale->user->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Paiement</div>
                    <div class="font-medium text-gray-800">{{ $sale->payment_method->label() }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Total</div>
                    <div class="font-semibold text-indigo-600">@mru($sale->total)</div>
                </div>
            </div>

            {{-- Articles --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-4">Articles de cette vente</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-3 py-2">Produit</th>
                                <th class="px-3 py-2 text-right">Quantité</th>
                                <th class="px-3 py-2 text-right">Prix unitaire</th>
                                <th class="px-3 py-2 text-right">Total ligne</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($sale->items as $item)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-gray-900">{{ $item->product->name ?? 'Produit supprimé' }}</td>
                                    <td class="px-3 py-2 text-right">{{ (int) $item->quantity }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">@mru($item->unit_price)</td>
                                    <td class="px-3 py-2 text-right font-medium">@mru($item->line_total)</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-200 font-semibold">
                                <td class="px-3 py-2" colspan="3">Total</td>
                                <td class="px-3 py-2 text-right text-indigo-600">@mru($sale->total)</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
