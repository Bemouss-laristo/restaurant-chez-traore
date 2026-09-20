<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Contrôle matière</h2>
            <a href="{{ route('purchases.create') }}" class="text-sm font-semibold px-3 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">+ Saisir un achat</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('reports._nav')

            <form method="GET" action="{{ route('reports.material') }}" class="bg-white shadow sm:rounded-lg p-3 flex flex-wrap items-center gap-2">
                <label class="text-sm text-gray-600">Du :</label>
                <input type="date" name="from" value="{{ $report['from'] }}" class="border-gray-300 rounded-md shadow-sm" />
                <label class="text-sm text-gray-600">au :</label>
                <input type="date" name="to" value="{{ $report['to'] }}" class="border-gray-300 rounded-md shadow-sm" />
                <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Afficher</button>
            </form>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Achats de la période</div><div class="text-2xl font-bold text-red-600 mt-1">@mru($report['totals']['purchases'])</div></div>
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Consommé par les ventes</div><div class="text-2xl font-bold text-gray-800 mt-1">@mru($report['totals']['consumed'])</div></div>
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Manquant (pertes / écarts)</div><div class="text-2xl font-bold text-red-700 mt-1">@mru($report['totals']['missing'])</div></div>
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Valeur du stock restant</div><div class="text-2xl font-bold text-indigo-600 mt-1">@mru($report['totals']['stock'])</div></div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-1">Article par article</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Lecture : <strong>Début + Acheté − Vendu = Reste attendu</strong>. Tout ce qui manque par rapport
                    au comptage apparaît dans « Manquant ». Un taux au-dessus de 5 % mérite une enquête.
                </p>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-3 py-2">Article</th>
                                <th class="px-3 py-2 text-right">Début</th>
                                <th class="px-3 py-2 text-right">Acheté</th>
                                <th class="px-3 py-2 text-right">Achats (MRU)</th>
                                <th class="px-3 py-2 text-right">Vendu</th>
                                <th class="px-3 py-2 text-right">Manquant</th>
                                <th class="px-3 py-2 text-right">Manquant (MRU)</th>
                                <th class="px-3 py-2 text-right">Reste</th>
                                <th class="px-3 py-2 text-right">Taux de perte</th>
                                <th class="px-3 py-2">Dernier comptage</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($report['rows'] as $row)
                                @php($item = $row['item'])
                                <tr class="{{ $row['lossRate'] > 5 ? 'bg-red-50' : '' }}">
                                    <td class="px-3 py-2 font-medium text-gray-900">
                                        {{ $item->name }}
                                        <span class="text-xs text-gray-400">({{ $item->unit->value }})</span>
                                        @if ($item->is_key)
                                            <span class="ms-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">clé</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-600">{{ number_format($row['openingQty'], 2, ',', ' ') }}</td>
                                    <td class="px-3 py-2 text-right text-green-700">+{{ number_format($row['purchaseQty'], 2, ',', ' ') }}</td>
                                    <td class="px-3 py-2 text-right">@mru($row['purchaseValue'])</td>
                                    <td class="px-3 py-2 text-right text-gray-700">−{{ number_format($row['consumedQty'], 2, ',', ' ') }}</td>
                                    <td class="px-3 py-2 text-right font-medium {{ $row['missingQty'] > 0 ? 'text-red-700' : 'text-gray-500' }}">{{ number_format($row['missingQty'], 2, ',', ' ') }}</td>
                                    <td class="px-3 py-2 text-right font-medium {{ $row['missingValue'] > 0 ? 'text-red-700' : 'text-gray-500' }}">@mru($row['missingValue'])</td>
                                    <td class="px-3 py-2 text-right font-semibold {{ $item->isLow() ? 'text-red-600' : 'text-gray-800' }}">{{ number_format($row['finalQty'], 2, ',', ' ') }}</td>
                                    <td class="px-3 py-2 text-right {{ $row['lossRate'] > 5 ? 'text-red-700 font-semibold' : 'text-gray-500' }}">{{ number_format($row['lossRate'], 1, ',', ' ') }} %</td>
                                    @php($lastCount = $item->lastCountedAt())
                                    <td class="px-3 py-2 text-xs {{ $lastCount ? 'text-gray-500' : 'text-amber-700' }}">{{ $lastCount ? $lastCount->format('d/m/Y') : 'jamais' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="px-3 py-6 text-center text-gray-500">Aucun article de stock. Crée d'abord tes articles clés dans le menu Stock.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-1">Tous les produits vendus sur la période</h3>
                <p class="text-sm text-gray-500 mb-4">Classés par chiffre d'affaires : ce qui rapporte vraiment, pas seulement ce qui se vend le plus.</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-3 py-2">Produit</th>
                                <th class="px-3 py-2 text-right">Quantité</th>
                                <th class="px-3 py-2 text-right">Chiffre d'affaires</th>
                                <th class="px-3 py-2 text-right">Coût matière</th>
                                <th class="px-3 py-2 text-right">Marge</th>
                                <th class="px-3 py-2 text-right">Marge %</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($products as $p)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-gray-900">{{ $p->name }}</td>
                                    <td class="px-3 py-2 text-right">{{ (int) $p->qty }}</td>
                                    <td class="px-3 py-2 text-right font-medium text-green-700">@mru($p->revenue)</td>
                                    <td class="px-3 py-2 text-right text-gray-600">@mru($p->cost)</td>
                                    <td class="px-3 py-2 text-right font-medium text-indigo-600">@mru($p->margin)</td>
                                    <td class="px-3 py-2 text-right text-gray-500">{{ $p->revenue > 0 ? number_format($p->margin / $p->revenue * 100, 0, ',', ' ') : 0 }} %</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">Aucune vente sur cette période.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-gray-500">Le coût matière vient du coût estimé de chaque produit (recette). Plus les recettes sont justes, plus la marge est fiable.</p>
            </div>
        </div>
    </div>
</x-app-layout>
