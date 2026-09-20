<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tableau de bord — {{ $businessDate->format('d/m/Y') }}
            <span class="text-sm font-normal text-gray-500">(ouvert de 19h à 2h — ventes comptées jusqu'à 5h)</span>
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Alertes --}}
            @foreach ($alerts as $alert)
                <div class="px-4 py-3 rounded-md border
                    {{ $alert['level'] === 'danger'
                        ? 'bg-red-50 border-red-200 text-red-800'
                        : 'bg-amber-50 border-amber-200 text-amber-800' }}">
                    ⚠ {{ $alert['message'] }}
                </div>
            @endforeach

            {{-- Cartes du jour --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white shadow sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Ventes du jour</div>
                    <div class="text-2xl font-bold text-green-600 mt-1">@mru($today['sales'])</div>
                </div>

                <div class="bg-white shadow sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Commandes du jour</div>
                    <div class="text-2xl font-bold text-gray-800 mt-1">{{ $today['orders'] }}</div>
                </div>

                @if ($canSeeFinance)
                    <a href="{{ route('expenses.index', ['date' => $businessDate->toDateString()]) }}" class="block bg-white shadow sm:rounded-lg p-5 hover:bg-gray-50">
                        <div class="text-sm text-gray-500">Dépenses du jour</div>
                        <div class="text-2xl font-bold text-red-600 mt-1">@mru($today['expenses'])</div>
                        <div class="text-xs text-indigo-600 mt-1">Voir le détail (caissiers inclus) →</div>
                    </a>

                    <div class="bg-white shadow sm:rounded-lg p-5">
                        <div class="text-sm text-gray-500">Bénéfice du jour</div>
                        <div class="text-2xl font-bold mt-1 {{ $today['profit'] < 0 ? 'text-red-600' : 'text-indigo-600' }}">
                            @mru($today['profit'])
                        </div>
                    </div>
                @else
                    <div class="bg-white shadow sm:rounded-lg p-5 sm:col-span-2">
                        <div class="text-sm text-gray-500">Solde de caisse</div>
                        <div class="text-2xl font-bold text-indigo-600 mt-1">
                            @if ($today['hasOpenSession'])
                                @mru($today['cashBalance'])
                            @else
                                <span class="text-gray-400 text-lg">Caisse fermée</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            @if ($canSeeFinance)
                {{-- Solde de caisse --}}
                <div class="bg-white shadow sm:rounded-lg p-5 flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500">Solde de caisse (théorique)</div>
                        <div class="text-2xl font-bold text-indigo-600 mt-1">
                            @if ($today['hasOpenSession'])
                                @mru($today['cashBalance'])
                            @else
                                <span class="text-gray-400">Aucune caisse ouverte</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('caisse.index') }}" class="text-sm text-indigo-600 hover:underline">Gérer la caisse →</a>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Tous les produits vendus ce mois --}}
                    <div class="bg-white shadow sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-medium text-gray-800">Produits vendus ce mois</h3>
                            <a href="{{ route('reports.material') }}" class="text-sm text-indigo-600 hover:underline">Contrôle matière →</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500">
                                        <th class="px-2 py-2">Produit</th>
                                        <th class="px-2 py-2 text-right">Qté</th>
                                        <th class="px-2 py-2 text-right">CA</th>
                                        <th class="px-2 py-2 text-right">Marge</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse ($monthProducts as $p)
                                        <tr>
                                            <td class="px-2 py-2 text-gray-800">{{ $p->name }}</td>
                                            <td class="px-2 py-2 text-right text-gray-600">{{ (int) $p->qty }}</td>
                                            <td class="px-2 py-2 text-right font-medium text-green-700">@mru($p->revenue)</td>
                                            <td class="px-2 py-2 text-right text-indigo-600">@mru($p->margin)</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="px-2 py-4 text-center text-gray-500">Pas encore de ventes ce mois.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Stock faible --}}
                    <div class="bg-white shadow sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-medium text-gray-800">Stock faible</h3>
                            <a href="{{ route('stock-items.index', ['low' => 1]) }}" class="text-sm text-indigo-600 hover:underline">Voir tout →</a>
                        </div>
                        @forelse ($lowStock as $item)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                                <span class="text-gray-800">{{ $item->name }}</span>
                                <span class="text-sm text-red-600">{{ (float) $item->quantity }} {{ $item->unit->value }} (seuil {{ (float) $item->alert_threshold }})</span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Aucun article en alerte. Tout va bien.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
