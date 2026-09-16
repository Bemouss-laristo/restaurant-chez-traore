<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rapport journalier</h2>
            @include('reports._export_buttons')
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('reports._nav')

            <form method="GET" action="{{ route('reports.daily') }}" class="bg-white shadow sm:rounded-lg p-3 flex items-center gap-2">
                <label class="text-sm text-gray-600">Date :</label>
                <input type="date" name="date" value="{{ $date->toDateString() }}" class="border-gray-300 rounded-md shadow-sm" />
                <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Afficher</button>
                <span class="text-xs text-gray-500">Ouvert de 19h à 2h. Les ventes jusqu'à 5h du matin comptent pour la soirée.</span>
            </form>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Ventes</div><div class="text-2xl font-bold text-green-600 mt-1">@mru($report['salesTotal'])</div></div>
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Commandes</div><div class="text-2xl font-bold text-gray-800 mt-1">{{ $report['orders'] }}</div></div>
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Dépenses</div><div class="text-2xl font-bold text-red-600 mt-1">@mru($report['expensesTotal'])</div></div>
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Bénéfice</div><div class="text-2xl font-bold mt-1 {{ $report['profit'] < 0 ? 'text-red-600' : 'text-indigo-600' }}">@mru($report['profit'])</div></div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-4">Ventes par mode de paiement</h3>
                    @forelse ($report['byPayment'] as $method => $total)
                        <div class="flex justify-between py-1 text-sm">
                            <span>{{ \App\Enums\PaymentMethod::from($method)->label() }}</span>
                            <span class="font-medium">@mru($total)</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Aucune vente.</p>
                    @endforelse
                </div>

                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-4">Dépenses par catégorie</h3>
                    @forelse ($report['byCategory'] as $cat => $total)
                        <div class="flex justify-between py-1 text-sm">
                            <span>{{ \App\Enums\ExpenseCategory::from($cat)->label() }}</span>
                            <span class="font-medium">@mru($total)</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Aucune dépense.</p>
                    @endforelse
                </div>

                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-4">Meilleures ventes</h3>
                    @forelse ($report['topProducts'] as $p)
                        <div class="flex justify-between py-1 text-sm">
                            <span>{{ $p->name }}</span>
                            <span class="text-gray-500">{{ (int) $p->qty }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Aucune vente.</p>
                    @endforelse
                </div>
            </div>

            {{-- Liste complète des produits vendus (quantités par produit) --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-4">Tous les produits vendus ce jour</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-3 py-2">Produit</th>
                                <th class="px-3 py-2 text-right">Quantité vendue</th>
                                <th class="px-3 py-2 text-right">Chiffre d'affaires</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($report['productsSold'] as $p)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-gray-900">{{ $p->name }}</td>
                                    <td class="px-3 py-2 text-right font-medium">{{ (int) $p->qty }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">@mru($p->revenue)</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-3 py-4 text-center text-gray-500">Aucune vente ce jour.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Détail des dépenses du jour (y compris celles des caissiers) --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <h3 class="font-medium text-gray-800">Détail des dépenses du jour</h3>
                    <a href="{{ route('expenses.index', ['date' => $date->toDateString()]) }}" class="text-sm text-indigo-600 hover:underline">Gérer ces dépenses →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-3 py-2">Heure</th>
                                <th class="px-3 py-2">Saisie par</th>
                                <th class="px-3 py-2">Catégorie</th>
                                <th class="px-3 py-2">Articles / description</th>
                                <th class="px-3 py-2 text-right">Montant</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($report['expensesList'] as $expense)
                                <tr>
                                    <td class="px-3 py-2 text-gray-600">{{ $expense->created_at->format('H:i') }}</td>
                                    <td class="px-3 py-2">{{ $expense->user->name ?? '—' }} <span class="text-xs text-gray-400">{{ $expense->user?->role->label() }}</span></td>
                                    <td class="px-3 py-2">{{ $expense->expense_category->label() }}</td>
                                    <td class="px-3 py-2 text-gray-700">{!! $expense->description ? nl2br(e($expense->description)) : '—' !!}</td>
                                    <td class="px-3 py-2 text-right font-medium">@mru($expense->amount)</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-3 py-4 text-center text-gray-500">Aucune dépense ce jour.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @include('reports._cancelled')
        </div>
    </div>
</x-app-layout>
