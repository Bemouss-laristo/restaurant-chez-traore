<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rapport hebdomadaire</h2>
            @include('reports._export_buttons')
        </div>
    </x-slot>

    @php
        $labels = $report['rows']->pluck('label');
        $salesData = $report['rows']->pluck('sales');
        $expData = $report['rows']->pluck('expenses');
        $profitData = $report['rows']->pluck('profit');
    @endphp

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('reports._nav')

            <form method="GET" action="{{ route('reports.weekly') }}" class="bg-white shadow sm:rounded-lg p-3 flex items-center gap-2">
                <label class="text-sm text-gray-600">Semaine du :</label>
                <input type="date" name="week" value="{{ $start->toDateString() }}" class="border-gray-300 rounded-md shadow-sm" />
                <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Afficher</button>
                <span class="text-sm text-gray-500">Du {{ $report['start']->format('d/m') }} au {{ $report['end']->format('d/m/Y') }}</span>
            </form>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Ventes</div><div class="text-2xl font-bold text-green-600 mt-1">@mru($report['totals']['sales'])</div></div>
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Dépenses</div><div class="text-2xl font-bold text-red-600 mt-1">@mru($report['totals']['expenses'])</div></div>
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Bénéfice</div><div class="text-2xl font-bold mt-1 {{ $report['totals']['profit'] < 0 ? 'text-red-600' : 'text-indigo-600' }}">@mru($report['totals']['profit'])</div></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-green-50 border border-green-200 rounded-md p-4">
                    <div class="text-sm text-green-700">Meilleur jour</div>
                    <div class="font-semibold text-green-800">
                        {{ $report['best'] ? $report['best']['date']->translatedFormat('l d/m').' — '.number_format($report['best']['sales'], 0, ',', ' ').' MRU' : '—' }}
                    </div>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-md p-4">
                    <div class="text-sm text-amber-700">Jour le plus faible</div>
                    <div class="font-semibold text-amber-800">
                        {{ $report['worst'] ? $report['worst']['date']->translatedFormat('l d/m').' — '.number_format($report['worst']['sales'], 0, ',', ' ').' MRU' : '—' }}
                    </div>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-4">Évolution de la semaine</h3>
                <div style="height:320px"><canvas id="weekChart"></canvas></div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', function () {
            new window.Chart(document.getElementById('weekChart'), {
                type: 'line',
                data: {
                    labels: @js($labels),
                    datasets: [
                        { label: 'Ventes', data: @js($salesData), borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.15)', tension: .3 },
                        { label: 'Dépenses', data: @js($expData), borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,.15)', tension: .3 },
                        { label: 'Bénéfice', data: @js($profitData), borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,.15)', tension: .3 },
                    ],
                },
                options: { responsive: true, maintainAspectRatio: false },
            });
        });
    </script>
</x-app-layout>
