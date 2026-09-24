<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rapport des achats</h2>
            <a href="{{ route('purchases.create') }}" class="text-sm font-semibold px-3 py-2 rounded-md bg-brand-600 text-white hover:bg-brand-700">+ Saisir un achat</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('reports._nav')

            <div class="bg-white shadow sm:rounded-lg p-3 flex flex-wrap items-center gap-2">
                @php($tab = fn (bool $a) => $a ? 'px-4 py-2 rounded-md text-sm font-semibold bg-brand-600 text-white' : 'px-4 py-2 rounded-md text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200')
                <a href="{{ route('reports.purchases', ['period' => 'day']) }}" class="{{ $tab($period === 'day') }}">Aujourd'hui</a>
                <a href="{{ route('reports.purchases', ['period' => 'week']) }}" class="{{ $tab($period === 'week') }}">Cette semaine</a>
                <a href="{{ route('reports.purchases', ['period' => 'month']) }}" class="{{ $tab($period === 'month') }}">Ce mois</a>
                <form method="GET" class="flex flex-wrap items-center gap-2 ms-2">
                    <input type="hidden" name="period" value="custom" />
                    <input type="date" name="from" value="{{ $report['from'] }}" class="border-gray-300 rounded-md shadow-sm" />
                    <input type="date" name="to" value="{{ $report['to'] }}" class="border-gray-300 rounded-md shadow-sm" />
                    <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Période libre</button>
                </form>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Total des achats</div><div class="text-2xl font-bold text-red-600 mt-1">@mru($report['total'])</div></div>
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Payé tout de suite</div><div class="text-2xl font-bold text-gray-800 mt-1">@mru($report['paidTotal'])</div></div>
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Pris à crédit</div><div class="text-2xl font-bold text-amber-600 mt-1">@mru($report['creditTotal'])</div></div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-4">Par jour</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead><tr class="text-left text-gray-500"><th class="px-3 py-2">Jour</th><th class="px-3 py-2 text-right">Saisies</th><th class="px-3 py-2 text-right">Total</th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($report['byDay'] as $day)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-700">{{ $day['date']->translatedFormat('D d/m') }}</td>
                                        <td class="px-3 py-2 text-right text-gray-500">{{ $day['count'] }}</td>
                                        <td class="px-3 py-2 text-right font-medium">@mru($day['total'])</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-3 py-4 text-center text-gray-500">Aucun achat sur la période.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-4">Par fournisseur</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead><tr class="text-left text-gray-500"><th class="px-3 py-2">Fournisseur</th><th class="px-3 py-2 text-right">Dont à crédit</th><th class="px-3 py-2 text-right">Total</th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($report['bySupplier'] as $supplier)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-700">{{ $supplier['name'] }}</td>
                                        <td class="px-3 py-2 text-right text-amber-600">@mru($supplier['credit'])</td>
                                        <td class="px-3 py-2 text-right font-medium">@mru($supplier['total'])</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-3 py-4 text-center text-gray-500">Aucun achat sur la période.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-1">Par article</h3>
                <p class="text-sm text-gray-500 mb-4">Les quantités réellement entrées en stock : à comparer aux factures des fournisseurs.</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead><tr class="text-left text-gray-500"><th class="px-3 py-2">Article</th><th class="px-3 py-2 text-right">Quantité achetée</th><th class="px-3 py-2 text-right">Montant</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($report['byArticle'] as $article)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-gray-900">{{ $article->name }} <span class="text-xs text-gray-400">({{ $article->unit }})</span></td>
                                    <td class="px-3 py-2 text-right">{{ number_format((float) $article->qty, 2, ',', ' ') }}</td>
                                    <td class="px-3 py-2 text-right font-medium">@mru($article->value)</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-3 py-4 text-center text-gray-500">Aucun article acheté sur la période.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-4">Détail des achats</h3>
                <div class="divide-y divide-gray-100">
                    @forelse ($report['list'] as $purchase)
                        <div class="py-2 text-sm">
                            <div class="flex flex-wrap justify-between gap-2">
                                <span class="text-gray-600">
                                    {{ $purchase->spent_at->format('d/m/Y') }}
                                    @if ($purchase->supplier) — <span class="font-medium text-gray-800">{{ $purchase->supplier->name }}</span> @endif
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $purchase->isCredit() ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800' }}">
                                        {{ $purchase->isCredit() ? 'À crédit' : $purchase->payment_method->label() }}
                                    </span>
                                </span>
                                <span class="font-medium">@mru($purchase->amount)</span>
                            </div>
                            <div class="text-gray-700">{!! nl2br(e($purchase->description)) !!}</div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Aucun achat sur la période.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
