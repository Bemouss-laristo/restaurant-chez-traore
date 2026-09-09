<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Réconciliation d'inventaire — {{ $dateLabel }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('reports._nav')

            {{-- ===== Résultats après comptage ===== --}}
            @isset($results)
                @php
                    $manques = $results->filter(fn ($r) => $r['ecart'] < 0);
                    $pertesValeur = abs($manques->sum('value'));
                    $hasManque = $manques->isNotEmpty();
                @endphp

                <div class="rounded-lg p-5 {{ $hasManque ? 'bg-red-50 border border-red-200' : 'bg-green-50 border border-green-200' }}">
                    @if ($hasManque)
                        <div class="text-red-800 font-semibold text-lg">⚠️ {{ $manques->count() }} article(s) manquant(s)</div>
                        <p class="text-red-700 text-sm mt-1">
                            Valeur estimée des manques ce soir :
                            <strong>{{ number_format($pertesValeur, 0, ',', ' ') }} MRU</strong>.
                            Un écart négatif = il manque physiquement plus que ce que les ventes enregistrées expliquent.
                        </p>
                    @else
                        <div class="text-green-800 font-semibold text-lg">✅ Aucun manque</div>
                        <p class="text-green-700 text-sm mt-1">Le stock compté correspond au stock théorique. Rien ne s'est évaporé ce soir.</p>
                    @endif
                </div>

                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-4">Écarts constatés</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="px-3 py-2">Article</th>
                                    <th class="px-3 py-2 text-right">Théorique</th>
                                    <th class="px-3 py-2 text-right">Compté</th>
                                    <th class="px-3 py-2 text-right">Écart</th>
                                    <th class="px-3 py-2 text-right">Valeur</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($results as $r)
                                    @php($neg = $r['ecart'] < 0)
                                    @php($pos = $r['ecart'] > 0)
                                    <tr class="{{ $neg ? 'bg-red-50' : '' }}">
                                        <td class="px-3 py-2 font-medium text-gray-900">{{ $r['name'] }} <span class="text-xs text-gray-400">({{ $r['unit'] }})</span></td>
                                        <td class="px-3 py-2 text-right text-gray-600">{{ rtrim(rtrim(number_format($r['theoretical'], 3, ',', ' '), '0'), ',') }}</td>
                                        <td class="px-3 py-2 text-right text-gray-900">{{ rtrim(rtrim(number_format($r['counted'], 3, ',', ' '), '0'), ',') }}</td>
                                        <td class="px-3 py-2 text-right font-semibold {{ $neg ? 'text-red-700' : ($pos ? 'text-amber-600' : 'text-gray-400') }}">
                                            {{ $r['ecart'] > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($r['ecart'], 3, ',', ' '), '0'), ',') }}
                                        </td>
                                        <td class="px-3 py-2 text-right {{ $neg ? 'text-red-700 font-medium' : 'text-gray-500' }}">
                                            {{ $r['value'] != 0 ? number_format($r['value'], 0, ',', ' ').' MRU' : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        Le stock théorique a été réaligné sur les quantités comptées, et chaque écart est enregistré dans
                        l'historique de l'article (raison « Ajustement manuel ») pour garder une trace.
                    </p>
                </div>
            @endisset

            {{-- ===== Formulaire de comptage ===== --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <p class="text-sm text-gray-600 mb-4">
                    À la fermeture, compte physiquement chaque article et saisis la quantité réelle.
                    L'appli calcule l'écart avec le stock théorique (déduit des ventes enregistrées).
                    <strong>« Vendu ce soir »</strong> = ce que les ventes tapées ont consommé, pour t'aider à vérifier.
                    Laisse vide les articles que tu ne comptes pas.
                </p>

                <form method="POST" action="{{ route('reconciliation.store') }}">
                    @csrf
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="px-3 py-2">Article</th>
                                    <th class="px-3 py-2 text-right">Reçu ce soir</th>
                                    <th class="px-3 py-2 text-right">Vendu ce soir</th>
                                    <th class="px-3 py-2 text-right">Stock théorique</th>
                                    <th class="px-3 py-2 text-right">Compté (réel)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($rows as $row)
                                    @php($item = $row['item'])
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-gray-900">{{ $item->name }} <span class="text-xs text-gray-400">({{ $item->unit->value }})</span></td>
                                        <td class="px-3 py-2 text-right text-green-700">{{ $row['purchaseIn'] > 0 ? '+'.rtrim(rtrim(number_format($row['purchaseIn'], 3, ',', ' '), '0'), ',') : '—' }}</td>
                                        <td class="px-3 py-2 text-right text-gray-600">{{ $row['saleOut'] > 0 ? rtrim(rtrim(number_format($row['saleOut'], 3, ',', ' '), '0'), ',') : '—' }}</td>
                                        <td class="px-3 py-2 text-right font-medium text-gray-900">{{ rtrim(rtrim(number_format((float) $item->quantity, 3, ',', ' '), '0'), ',') }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <input type="number" step="0.001" min="0" name="counted[{{ $item->id }}]"
                                                class="w-28 border-gray-300 rounded-md shadow-sm text-right"
                                                placeholder="—" />
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-3 py-6 text-center text-gray-400">Aucun article de stock. Ajoute d'abord tes articles (pain tacos, poulet…) dans le stock.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-5 flex items-center justify-end">
                        <button class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                            Calculer les écarts
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
