<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Historique des ventes</h2>
            <a href="{{ route('sales.create') }}"
                class="inline-flex items-center px-4 py-2 bg-brand-600 text-white text-sm font-semibold rounded-md hover:bg-brand-700">
                + Nouvelle vente
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                @if (session('status'))
                    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
                @endif
                <p class="mb-4 text-sm text-gray-500">Pour annuler une vente (client qui annule), ouvre-la avec « Voir / Annuler ».</p>
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
                                <th class="px-4 py-2 text-right">Détail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($sales as $sale)
                                <tr class="hover:bg-gray-50 {{ $sale->isCancelled() ? 'bg-red-50 text-gray-400' : '' }}">
                                    <td class="px-4 py-3 font-mono text-xs">
                                        <a href="{{ route('sales.show', $sale) }}" class="text-brand-600 hover:underline">{{ $sale->sale_number }}</a>
                                        @if ($sale->isCancelled())
                                            <span class="ms-1 px-1.5 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-700">ANNULÉE</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $sale->sold_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $sale->user->name }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $sale->items_count }}</td>
                                    <td class="px-4 py-3">{{ $sale->payment_method->label() }}</td>
                                    <td class="px-4 py-3 text-right font-medium {{ $sale->isCancelled() ? 'text-gray-400' : '' }}">@mru($sale->total)</td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" onclick="printTicket('{{ route('sales.receipt', $sale) }}')"
                                            class="px-2 text-gray-600 hover:underline" title="Réimprimer le ticket">🖨️</button>
                                        <a href="{{ route('sales.show', $sale) }}" class="text-brand-600 hover:underline">
                                            {{ ! $sale->isCancelled() && $sale->canBeCancelledBy(auth()->user()) ? 'Voir / Annuler' : 'Voir' }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">Aucune vente enregistrée.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $sales->links() }}</div>
            </div>
        </div>
    </div>
    @include('partials.print-ticket')
</x-app-layout>
