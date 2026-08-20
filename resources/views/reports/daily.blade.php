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
        </div>
    </div>
</x-app-layout>
