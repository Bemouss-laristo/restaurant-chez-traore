{{-- Ventes annulées sur la période : nombre, montant et motif. --}}
@php($cancelled = $report['cancelled'])
<div class="bg-white shadow sm:rounded-lg p-6">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
        <h3 class="font-medium text-gray-800">Commandes / ventes annulées</h3>
        <div class="text-sm">
            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $cancelled['count'] > 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600' }}">
                {{ $cancelled['count'] }} annulée(s)
            </span>
            <span class="ms-3 text-gray-500">Montant retiré : <span class="font-semibold text-red-700">@mru($cancelled['total'])</span></span>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead>
                <tr class="text-left text-gray-500">
                    <th class="px-3 py-2">Vente</th>
                    <th class="px-3 py-2">Date</th>
                    <th class="px-3 py-2">Client</th>
                    <th class="px-3 py-2">Caissier</th>
                    <th class="px-3 py-2">Annulée par</th>
                    <th class="px-3 py-2">Motif</th>
                    <th class="px-3 py-2 text-right">Montant</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($cancelled['rows'] as $row)
                    @php($sale = $row['sale'])
                    <tr>
                        <td class="px-3 py-2 font-mono text-xs">
                            <a href="{{ route('sales.show', $sale) }}" class="text-indigo-600 hover:underline">{{ $sale->sale_number }}</a>
                        </td>
                        <td class="px-3 py-2 text-gray-600">{{ $sale->sold_at->format('d/m H:i') }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ $row['customer'] ?? '—' }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ $sale->user->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ $sale->cancelledBy->name ?? '—' }} <span class="text-xs text-gray-400">{{ $sale->cancelled_at->format('d/m H:i') }}</span></td>
                        <td class="px-3 py-2 text-gray-800">{{ $sale->cancellation_reason }}</td>
                        <td class="px-3 py-2 text-right font-medium">@mru($sale->total)</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-4 text-center text-gray-500">Aucune annulation sur cette période.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
