<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rapport mensuel</h2>
            @include('reports._export_buttons')
        </div>
    </x-slot>

    @php
        $dayLabels = $report['perDay']->pluck('day');
        $daySales = $report['perDay']->pluck('sales');
        $catLabels = collect($report['byCategory'])->keys()->map(fn ($k) => \App\Enums\ExpenseCategory::from($k)->label())->values();
        $catData = collect($report['byCategory'])->values();
    @endphp

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('reports._nav')

            <form method="GET" action="{{ route('reports.monthly') }}" class="bg-white shadow sm:rounded-lg p-3 flex items-center gap-2">
                <label class="text-sm text-gray-600">Mois :</label>
                <input type="month" name="month" value="{{ $ref->format('Y-m') }}" class="border-gray-300 rounded-md shadow-sm" />
                <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Afficher</button>
                <span class="text-sm text-gray-500">{{ $report['start']->translatedFormat('F Y') }}</span>
            </form>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Chiffre d'affaires</div><div class="text-2xl font-bold text-green-600 mt-1">@mru($report['salesTotal'])</div></div>
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Dépenses</div><div class="text-2xl font-bold text-red-600 mt-1">@mru($report['expensesTotal'])</div></div>
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Bénéfice</div><div class="text-2xl font-bold mt-1 {{ $report['profit'] < 0 ? 'text-red-600' : 'text-brand-600' }}">@mru($report['profit'])</div></div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-4">Ventes par jour</h3>
                <div style="height:320px"><canvas id="monthChart"></canvas></div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-4">Répartition des dépenses</h3>
                    @if ($catData->sum() > 0)
                        <div style="height:280px"><canvas id="catChart"></canvas></div>
                    @else
                        <p class="text-sm text-gray-500">Aucune dépense ce mois-ci.</p>
                    @endif
                </div>

                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-4">Produits les plus vendus</h3>
                    @forelse ($report['topProducts'] as $p)
                        <div class="flex justify-between py-2 border-b border-gray-100 last:border-0 text-sm">
                            <span>{{ $p->name }}</span>
                            <span class="text-gray-500">{{ (int) $p->qty }} vendus · @mru($p->revenue)</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Aucune vente ce mois-ci.</p>
                    @endforelse
                </div>
            </div>

            {{-- Tous les produits vendus du mois : ce qui rapporte vraiment --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-1">Tous les produits vendus ce mois</h3>
                <p class="text-sm text-gray-500 mb-4">Classés par chiffre d'affaires. La marge tient compte du coût matière estimé de chaque recette.</p>
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
                            @forelse ($report['productsSold'] as $p)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-gray-900">{{ $p->name }}</td>
                                    <td class="px-3 py-2 text-right">{{ (int) $p->qty }}</td>
                                    <td class="px-3 py-2 text-right font-medium text-green-700">@mru($p->revenue)</td>
                                    <td class="px-3 py-2 text-right text-gray-600">@mru($p->cost)</td>
                                    <td class="px-3 py-2 text-right font-medium text-brand-600">@mru($p->margin)</td>
                                    <td class="px-3 py-2 text-right text-gray-500">{{ $p->revenue > 0 ? number_format($p->margin / $p->revenue * 100, 0, ',', ' ') : 0 }} %</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">Aucune vente ce mois.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @include('reports._cancelled')
        </div>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', function () {
            new window.Chart(document.getElementById('monthChart'), {
                type: 'bar',
                data: {
                    labels: @js($dayLabels),
                    datasets: [{ label: 'Ventes (MRU)', data: @js($daySales), backgroundColor: '#16a34a' }],
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
            });

            @if ($catData->sum() > 0)
            new window.Chart(document.getElementById('catChart'), {
                type: 'doughnut',
                data: {
                    labels: @js($catLabels),
                    datasets: [{
                        data: @js($catData),
                        backgroundColor: ['#4f46e5', '#16a34a', '#dc2626', '#d97706', '#0891b2', '#9d174d', '#64748b'],
                    }],
                },
                options: { responsive: true, maintainAspectRatio: false },
            });
            @endif
        });
    </script>
</x-app-layout>
