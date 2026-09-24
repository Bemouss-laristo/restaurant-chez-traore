<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Stock</h2>
            <a href="{{ route('stock-items.create') }}"
                class="inline-flex items-center px-4 py-2 bg-brand-600 text-white text-sm font-semibold rounded-md hover:bg-brand-700">
                + Nouvel article
            </a>
        </div>
    </x-slot>

    @php
        $rows = $items->map(fn ($i) => trim($i->name.' '.($i->supplier->name ?? '').' '.$i->unit->value))->values();
        $lows = $items->map(fn ($i) => $i->isLow())->values();
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">{{ session('error') }}</div>
            @endif

            @if ($lowCount > 0)
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-md">
                    ⚠ {{ $lowCount }} article(s) sous le seuil d'alerte.
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6" x-data="liveSearch({
                rows: @js($rows),
                lows: @js($lows),
                onlyLow: false,
                keep(text, isLow) { return (! this.onlyLow || isLow) && this.match(text); },
                get shown() { return this.rows.filter((row, i) => this.keep(row, this.lows[i])).length; },
            })">
                <div class="mb-4 flex flex-wrap gap-2 items-center">
                    <input type="search" x-model="search" placeholder="Rechercher un article…" autocomplete="off"
                        class="border-gray-300 rounded-md shadow-sm w-full max-w-sm" />
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" x-model="onlyLow" class="rounded border-gray-300" />
                        Stock faible uniquement
                    </label>
                    <span class="text-sm text-gray-500" x-show="search.trim() !== '' || onlyLow"
                        x-text="shown + ' article(s) sur {{ $items->count() }}'"></span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-4 py-2">Article</th>
                                <th class="px-4 py-2 text-right">Quantité</th>
                                <th class="px-4 py-2 text-right">Seuil</th>
                                <th class="px-4 py-2 text-right">Coût unit.</th>
                                <th class="px-4 py-2">Statut</th>
                                <th class="px-4 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($items as $item)
                                <tr class="{{ $item->isLow() ? 'bg-red-50' : '' }}"
                                    x-show="keep(@js($rows[$loop->index]), {{ $item->isLow() ? 'true' : 'false' }})">
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ $item->name }}
                                        @if ($item->is_key)
                                            <span class="ms-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">clé</span>
                                        @endif
                                        @php($lastCount = $item->lastCountedAt())
                                        <div class="text-xs {{ $lastCount ? 'text-gray-400' : 'text-amber-700' }}">
                                            {{ $lastCount ? 'Dernier comptage : '.$lastCount->format('d/m/Y') : 'Jamais compté' }}
                                        </div>
                                        @if ($item->pack_label && (float) $item->pack_quantity > 0)
                                            <div class="text-xs text-gray-400">{{ $item->pack_label }} = {{ rtrim(rtrim(number_format((float) $item->pack_quantity, 3, ',', ' '), '0'), ',') }} {{ $item->unit->value }}</div>
                                        @endif
                                        @if ($item->supplier)
                                            <div class="text-xs text-gray-500">
                                                {{ $item->supplier->name }}@if (($item->default_payment_method?->value ?? null) === 'credit') — à crédit @endif
                                                @if ($item->hasAgreedPrice())
                                                    — @mru($item->agreed_unit_price) le {{ $item->unit->value }}
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">{{ (float) $item->quantity }} {{ $item->unit->value }}</td>
                                    <td class="px-4 py-3 text-right text-gray-500">{{ (float) $item->alert_threshold }}</td>
                                    <td class="px-4 py-3 text-right text-gray-500">@mru($item->unit_cost)</td>
                                    <td class="px-4 py-3">
                                        @if ($item->isLow())
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Faible</span>
                                        @else
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">OK</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('stock-items.edit', $item) }}" title="Gérer"
                                                class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-sm font-medium text-brand-600 transition hover:bg-brand-50">
                                                <x-icon name="pencil" class="h-4 w-4" /> Gérer
                                            </a>
                                            <form method="POST" action="{{ route('stock-items.destroy', $item) }}"
                                                onsubmit="return confirm('Supprimer « {{ addslashes($item->name) }} » ? S\'il a déjà servi, il sera archivé : son historique reste dans les rapports.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Supprimer" aria-label="Supprimer {{ $item->name }}"
                                                    class="rounded-lg p-1.5 text-cocoa-400 transition hover:bg-red-50 hover:text-red-600">
                                                    <x-icon name="trash" class="h-4 w-4" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Aucun article de stock.</td></tr>
                            @endforelse
                            @if ($items->isNotEmpty())
                                <tr x-show="shown === 0">
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                        Aucun article ne correspond à cette recherche.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('partials.live-search')
</x-app-layout>
