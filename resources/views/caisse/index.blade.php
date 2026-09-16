<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Caisse</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">{{ session('error') }}</div>
            @endif

            @if ($current === null)
                {{-- Aucune caisse ouverte : formulaire d'ouverture --}}
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <h3 class="font-medium text-gray-800 mb-1">Ouvrir la caisse</h3>
                    <p class="text-sm text-gray-500 mb-4">Saisis le fond de caisse (l'argent liquide déjà présent dans le tiroir).</p>
                    <form method="POST" action="{{ route('caisse.open') }}" class="flex flex-wrap items-end gap-4">
                        @csrf
                        <div>
                            <x-input-label for="opening_float" value="Fond de caisse (MRU)" />
                            <x-text-input id="opening_float" name="opening_float" type="number" step="0.01" min="0"
                                class="mt-1 block w-48" :value="old('opening_float', 0)" required autofocus />
                            <x-input-error :messages="$errors->get('opening_float')" class="mt-2" />
                        </div>
                        <x-primary-button>Ouvrir la caisse</x-primary-button>
                    </form>
                </div>
            @else
                {{-- Caisse ouverte : suivi + clôture --}}
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-medium text-gray-800">Caisse ouverte</h3>
                            <p class="text-sm text-gray-500">Depuis le {{ $current->opened_at->format('d/m/Y à H:i') }} — {{ $current->user->name }}</p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Ouverte</span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                        <div class="bg-gray-50 rounded-md p-4">
                            <div class="text-xs text-gray-500">Fond de caisse</div>
                            <div class="text-lg font-semibold text-gray-800">@mru($current->opening_float)</div>
                        </div>
                        <div class="bg-green-50 rounded-md p-4">
                            <div class="text-xs text-gray-500">Ventes espèces</div>
                            <div class="text-lg font-semibold text-green-700">+@mru($cashSales)</div>
                        </div>
                        <div class="bg-red-50 rounded-md p-4">
                            <div class="text-xs text-gray-500">Dépenses espèces</div>
                            <div class="text-lg font-semibold text-red-700">−@mru($cashExpenses)</div>
                            <a href="{{ route('cashier-expenses.index') }}" class="text-xs text-indigo-600 hover:underline">+ Noter une dépense</a>
                        </div>
                        <div class="bg-indigo-50 rounded-md p-4">
                            <div class="text-xs text-gray-500">Caisse théorique</div>
                            <div class="text-lg font-semibold text-indigo-700">@mru($expected)</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <h3 class="font-medium text-gray-800 mb-1">Clôturer la caisse</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Compte l'argent réellement présent dans le tiroir et saisis-le. L'écart avec le théorique
                        (<span class="font-medium">@mru($expected)</span>) sera calculé automatiquement.
                    </p>
                    <form method="POST" action="{{ route('caisse.close') }}" class="flex flex-wrap items-end gap-4"
                        onsubmit="return confirm('Clôturer définitivement cette caisse ?');">
                        @csrf
                        <div>
                            <x-input-label for="counted_cash" value="Caisse réelle comptée (MRU)" />
                            <x-text-input id="counted_cash" name="counted_cash" type="number" step="0.01" min="0"
                                class="mt-1 block w-48" :value="old('counted_cash')" required />
                            <x-input-error :messages="$errors->get('counted_cash')" class="mt-2" />
                        </div>
                        <button class="px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-md hover:bg-red-700">
                            Clôturer
                        </button>
                    </form>
                </div>
            @endif

            {{-- Historique --}}
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <h3 class="font-medium text-gray-800 mb-4">Historique des caisses clôturées</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-3 py-2">Clôturée le</th>
                                <th class="px-3 py-2">Par</th>
                                <th class="px-3 py-2 text-right">Théorique</th>
                                <th class="px-3 py-2 text-right">Réelle</th>
                                <th class="px-3 py-2 text-right">Écart</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($history as $session)
                                @php($diff = (float) $session->difference)
                                <tr>
                                    <td class="px-3 py-2 text-gray-600">{{ $session->closed_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $session->user->name }}</td>
                                    <td class="px-3 py-2 text-right">@mru($session->expected_cash)</td>
                                    <td class="px-3 py-2 text-right">@mru($session->counted_cash)</td>
                                    <td class="px-3 py-2 text-right font-medium
                                        {{ $diff < 0 ? 'text-red-600' : ($diff > 0 ? 'text-amber-600' : 'text-green-600') }}">
                                        {{ $diff > 0 ? '+' : '' }}@mru($diff)
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-3 py-4 text-center text-gray-500">Aucune caisse clôturée pour l'instant.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $history->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
