<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Historique des ventes</h2>
            <a href="{{ route('sales.create') }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                + Nouvelle vente
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-4 py-2">N°</th>
                                <th class="px-4 py-2">Date</th>
                                <th class="px-4 py-2">Caissier</th>
                                <th class="px-4 py-2 text-right">Articles</th>
                                <th class="px-4 py-2">Paiement</th>
                                <th class="px-4 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($sales as $sale)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $sale->sale_number }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $sale->sold_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $sale->user->name }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $sale->items_count }}</td>
                                    <td class="px-4 py-3">{{ $sale->payment_method->label() }}</td>
                                    <td class="px-4 py-3 text-right font-medium">@mru($sale->total)</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Aucune vente enregistrée.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $sales->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
