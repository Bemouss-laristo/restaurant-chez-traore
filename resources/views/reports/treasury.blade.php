<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Trésorerie et budget — {{ $monthLabel }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('reports._nav')

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif

            <form method="GET" class="bg-white shadow sm:rounded-lg p-3 flex flex-wrap items-center gap-2">
                <label class="text-sm text-gray-600">Mois :</label>
                <input type="month" name="month" value="{{ $report['month'] }}" class="border-gray-300 rounded-md shadow-sm" />
                <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Afficher</button>
            </form>

            {{-- Argent du mois --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white shadow sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Argent entré (ventes)</div>
                    <div class="text-2xl font-bold text-green-600 mt-1">@mru($report['cashIn'])</div>
                    <div class="mt-2 space-y-1 text-xs text-gray-500">
                        @foreach ($report['salesByMethod'] as $method => $total)
                            <div class="flex justify-between"><span>{{ \App\Enums\PaymentMethod::from($method)->label() }}</span><span>@mru($total)</span></div>
                        @endforeach
                    </div>
                </div>
                <div class="bg-white shadow sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Argent sorti</div>
                    <div class="text-2xl font-bold text-red-600 mt-1">@mru($report['cashOut'])</div>
                    <div class="mt-2 space-y-1 text-xs text-gray-500">
                        <div class="flex justify-between"><span>Dépenses payées</span><span>@mru($report['paidExpenses'])</span></div>
                        <div class="flex justify-between"><span>Factures fournisseurs réglées</span><span>@mru($report['supplierPayments'])</span></div>
                    </div>
                </div>
                <div class="bg-white shadow sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Résultat de trésorerie</div>
                    <div class="text-2xl font-bold mt-1 {{ $report['net'] < 0 ? 'text-red-600' : 'text-indigo-600' }}">@mru($report['net'])</div>
                    <div class="mt-2 text-xs text-gray-500">Ce qui reste réellement après tout ce qui est sorti ce mois-ci.</div>
                </div>
            </div>

            {{-- Position nette (fiable) et fonds de roulement (estimation) --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-1">Où tu en es vraiment</h3>
                <p class="text-sm text-gray-500 mb-4">
                    « Si je paie aujourd'hui tout ce que je dois, il me reste combien ? »
                </p>

                @if ($report['available'] === null)
                    <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-md mb-4">
                        Aucun relevé d'argent disponible. <a href="{{ route('balances.index') }}" class="underline font-medium">Saisis ton premier relevé</a>
                        (caisse + Bankily + Masrivi + Sedad) pour obtenir la position nette.
                    </div>
                @endif

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-gray-50 rounded-md p-4">
                        <div class="text-xs text-gray-500">Argent disponible</div>
                        <div class="text-lg font-semibold text-gray-800">{{ $report['available'] === null ? '—' : number_format($report['available'], 0, ',', ' ').' MRU' }}</div>
                        @if ($report['balance'])
                            <div class="text-xs text-gray-400 mt-1">Relevé du {{ $report['balance']->recorded_on->format('d/m/Y') }}</div>
                        @endif
                    </div>
                    <div class="bg-red-50 rounded-md p-4">
                        <div class="text-xs text-gray-500">Dettes fournisseurs</div>
                        <div class="text-lg font-semibold text-red-700">−@mru($report['supplierDebt'])</div>
                    </div>
                    <div class="bg-red-50 rounded-md p-4">
                        <div class="text-xs text-gray-500">Salaires restant à payer</div>
                        <div class="text-lg font-semibold text-red-700">−@mru($report['salaryDue'])</div>
                    </div>
                    <div class="rounded-md p-4 {{ ($report['netPosition'] ?? 0) < 0 ? 'bg-red-100' : 'bg-indigo-50' }}">
                        <div class="text-xs text-gray-500">Position nette</div>
                        <div class="text-lg font-bold {{ ($report['netPosition'] ?? 0) < 0 ? 'text-red-700' : 'text-indigo-700' }}">
                            {{ $report['netPosition'] === null ? '—' : number_format($report['netPosition'], 0, ',', ' ').' MRU' }}
                        </div>
                        <div class="text-xs text-gray-400 mt-1">Chiffre fiable, sans le stock</div>
                    </div>
                </div>

                {{-- Estimation avec le stock --}}
                <div class="mt-5 border-t border-gray-200 pt-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <div class="text-sm text-gray-500">
                                Fonds de roulement avec le stock
                                <span class="ms-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">estimation</span>
                            </div>
                            <div class="text-xl font-semibold {{ $report['workingCapital'] < 0 ? 'text-red-700' : 'text-gray-800' }}">@mru($report['workingCapital'])</div>
                        </div>
                        <div class="text-xs text-gray-500 text-right">
                            <div>Valeur du stock comptée dedans : <span class="font-medium text-gray-700">@mru($report['stockValue'])</span></div>
                            <div>
                                Dernier comptage :
                                <span class="font-medium text-gray-700">{{ $report['stockCountedAt'] ? $report['stockCountedAt']->format('d/m/Y') : 'jamais' }}</span>
                            </div>
                            @if ($report['keyItemsNeverCounted'] > 0)
                                <div class="text-amber-700">{{ $report['keyItemsNeverCounted'] }} article(s) clé(s) jamais compté(s)</div>
                            @endif
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">
                        Ce chiffre ne vaut que ce que valent les comptages. Compte les articles clés chaque soir
                        (<a href="{{ route('reconciliation.index') }}" class="text-indigo-600 hover:underline">comptage du soir</a>)
                        et il deviendra fiable en deux semaines.
                    </p>
                </div>

                @if ($report['creditExpenses'] > 0)
                    <p class="mt-4 text-sm text-amber-700">
                        ⚠ {{ number_format($report['creditExpenses'], 0, ',', ' ') }} MRU de marchandise ont été pris à crédit ce mois-ci : la charge est déjà comptée, mais l'argent sortira à la facture.
                    </p>
                @endif
            </div>

            {{-- Budgets --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-1">Plafonds du mois</h3>
                <p class="text-sm text-gray-500 mb-4">Fixe un plafond par catégorie. La barre passe au rouge dès que la dépense le dépasse. Laisse vide pour ne pas suivre une catégorie.</p>

                <form method="POST" action="{{ route('reports.budgets') }}">
                    @csrf
                    <input type="hidden" name="month" value="{{ $report['month'] }}" />
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="px-3 py-2">Catégorie</th>
                                    <th class="px-3 py-2 text-right">Plafond (MRU)</th>
                                    <th class="px-3 py-2 text-right">Dépensé</th>
                                    <th class="px-3 py-2">Consommation</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($report['categories'] as $category)
                                    @php($pct = $category['budget'] > 0 ? min(100, round($category['spent'] / $category['budget'] * 100)) : 0)
                                    @php($over = $category['budget'] > 0 && $category['spent'] > $category['budget'])
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-gray-900">{{ $category['label'] }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <input type="number" step="1" min="0" name="budgets[{{ $category['value'] }}]"
                                                value="{{ $category['budget'] > 0 ? (int) $category['budget'] : '' }}"
                                                class="w-32 text-right border-gray-300 rounded-md shadow-sm" placeholder="—" />
                                        </td>
                                        <td class="px-3 py-2 text-right font-medium {{ $over ? 'text-red-700' : 'text-gray-800' }}">@mru($category['spent'])</td>
                                        <td class="px-3 py-2">
                                            @if ($category['budget'] > 0)
                                                <div class="w-full bg-gray-100 rounded-full" style="height:8px">
                                                    <div class="rounded-full {{ $over ? 'bg-red-600' : ($pct > 80 ? 'bg-amber-500' : 'bg-green-600') }}" style="height:8px;width: {{ $pct }}%"></div>
                                                </div>
                                                <div class="text-xs mt-1 {{ $over ? 'text-red-700' : 'text-gray-500' }}">
                                                    {{ $over ? 'Dépassé de '.number_format($category['spent'] - $category['budget'], 0, ',', ' ').' MRU' : $pct.' % du plafond' }}
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400">Pas de plafond</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <x-primary-button>Enregistrer les plafonds</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
