<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Contrôle du stock (par jour)</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('reports._nav')

            <form method="GET" action="{{ route('reports.stock') }}" class="bg-white shadow sm:rounded-lg p-3 flex flex-wrap items-center gap-2">
                <label class="text-sm text-gray-600">Date :</label>
                <input type="date" name="date" value="{{ $date }}" class="border-gray-300 rounded-md shadow-sm" />
                <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Afficher</button>
                <span class="text-xs text-gray-500">Ouvert de 19h à 2h. Les mouvements jusqu'à 5h du matin comptent pour la soirée.</span>
            </form>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <p class="text-sm text-gray-600 mb-4">
                    Pour chaque article : ce que tu as <strong>acheté</strong> (entrées), ce qui a été
                    <strong>vendu / sorti</strong> ce jour, et le <strong>reste actuel</strong>.
                    Compare le « reste » à ce que tu comptes physiquement — un écart signale une anomalie.
                </p>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-3 py-2">Article</th>
                                <th class="px-3 py-2 text-right">Acheté (entrées)</th>
                                <th class="px-3 py-2 text-right">Vendu / sorti</th>
                                <th class="px-3 py-2 text-right">Reste actuel</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($rows as $row)
                                @php($item = $row['item'])
                                <tr class="{{ $item->isLow() ? 'bg-red-50' : '' }}">
                                    <td class="px-3 py-2 font-medium text-gray-900">{{ $item->name }} <span class="text-xs text-gray-400">({{ $item->unit->value }})</span></td>
                                    <td class="px-3 py-2 text-right text-green-700">+{{ (float) $row['in'] }}</td>
                                    <td class="px-3 py-2 text-right text-red-700">−{{ (float) $row['out'] }}</td>
                                    <td class="px-3 py-2 text-right font-semibold {{ $item->isLow() ? 'text-red-600' : 'text-gray-800' }}">{{ (float) $item->quantity }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-3 py-6 text-center text-gray-500">Aucun article de stock. Crée tes articles clés (pain arabe, poulet…) dans le menu Stock.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
