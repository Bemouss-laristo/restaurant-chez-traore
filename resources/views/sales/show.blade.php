<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Vente {{ $sale->sale_number }}</h2>
            <div class="flex items-center gap-4">
                <button type="button" onclick="printTicket('{{ route('sales.receipt', $sale) }}')"
                   class="text-sm font-semibold px-3 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">🖨️ Imprimer le ticket</button>
                <a href="{{ route('sales.index') }}" class="text-sm text-indigo-600 hover:underline">← Retour à l'historique</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">{{ session('error') }}</div>
            @endif

            @if ($sale->isCancelled())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                    <div class="font-semibold">Vente annulée</div>
                    <div class="text-sm mt-1">
                        Le {{ $sale->cancelled_at->format('d/m/Y à H:i') }} par {{ $sale->cancelledBy->name ?? '—' }}
                        — Motif : « {{ $sale->cancellation_reason }} »
                    </div>
                    <div class="text-xs mt-1">Cette vente ne compte plus dans la caisse ni dans les rapports.</div>
                </div>
            @endif

            @if ($order)
                <div class="bg-white shadow sm:rounded-lg px-6 py-4 text-sm text-gray-700">
                    Commande en ligne <span class="font-mono">{{ $order->order_number }}</span>
                    — {{ $order->customer_name }} ({{ $order->customer_phone }})
                </div>
            @endif

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
                    <div class="font-semibold {{ $sale->isCancelled() ? 'text-gray-400' : 'text-indigo-600' }}">@mru($sale->total)</div>
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

            {{-- Annulation --}}
            @if ($canCancel)
                <div class="bg-white shadow sm:rounded-lg p-6 border border-red-200">
                    <h3 class="font-medium text-red-700 mb-1">Annuler cette vente</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        À utiliser si le client a annulé sa commande. La vente sera retirée de la caisse et des rapports,
                        et les ingrédients remis en stock. Le motif est obligatoire et reste visible par le gérant.
                    </p>
                    <form method="POST" action="{{ route('sales.cancel', $sale) }}" class="flex flex-wrap items-end gap-3"
                        onsubmit="return confirm('Annuler définitivement la vente {{ $sale->sale_number }} ?');">
                        @csrf
                        <div class="flex-1 min-w-0 w-full">
                            <x-input-label for="cancellation_reason" value="Motif de l'annulation" />
                            <x-text-input id="cancellation_reason" name="cancellation_reason" type="text" class="mt-1 block w-full"
                                :value="old('cancellation_reason')" placeholder="Ex : le client a annulé sa commande" required maxlength="255" />
                            <x-input-error :messages="$errors->get('cancellation_reason')" class="mt-2" />
                        </div>
                        <button class="px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-md hover:bg-red-700">
                            Annuler la vente
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
    @include('partials.print-ticket')
</x-app-layout>
