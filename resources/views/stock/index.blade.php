<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Stock</h2>
            <a href="{{ route('stock-items.create') }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                + Nouvel article
            </a>
        </div>
    </x-slot>

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
                    <a href="{{ route('stock-items.index', ['low' => 1]) }}" class="underline">Voir seulement ceux-là</a>
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6">
                <form method="GET" action="{{ route('stock-items.index') }}" class="mb-4 flex flex-wrap gap-2 items-center">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Rechercher un article…"
                        class="border-gray-300 rounded-md shadow-sm w-full max-w-sm" />
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="low" value="1" @checked($onlyLow) class="rounded border-gray-300" />
                        Stock faible uniquement
                    </label>
                    <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Filtrer</button>
                </form>

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
                                <tr class="{{ $item->isLow() ? 'bg-red-50' : '' }}">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $item->name }}</td>
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
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('stock-items.edit', $item) }}" class="text-indigo-600 hover:underline">Gérer</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Aucun article de stock.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $items->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
