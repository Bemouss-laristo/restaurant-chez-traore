<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Soldes — argent disponible</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('reports._nav')

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">{{ $errors->first() }}</div>
            @endif

            @if ($latest)
                @php($variation = $previous ? $latest->total() - $previous->total() : null)
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white shadow sm:rounded-lg p-5">
                        <div class="text-sm text-gray-500">Argent disponible</div>
                        <div class="text-2xl font-bold text-brand-600 mt-1">@mru($latest->total())</div>
                        <div class="text-xs text-gray-400 mt-1">Relevé du {{ $latest->recorded_on->format('d/m/Y') }}</div>
                    </div>
                    <div class="bg-white shadow sm:rounded-lg p-5">
                        <div class="text-sm text-gray-500">Depuis le relevé précédent</div>
                        <div class="text-2xl font-bold mt-1 {{ $variation === null ? 'text-gray-400' : ($variation < 0 ? 'text-red-600' : 'text-green-600') }}">
                            {{ $variation === null ? '—' : ($variation > 0 ? '+' : '') }}@if ($variation !== null)@mru($variation)@endif
                        </div>
                        <div class="text-xs text-gray-400 mt-1">{{ $previous ? 'Relevé du '.$previous->recorded_on->format('d/m/Y') : 'Pas encore de comparaison' }}</div>
                    </div>
                    <div class="bg-white shadow sm:rounded-lg p-5">
                        <div class="text-sm text-gray-500">Répartition</div>
                        <div class="mt-1 space-y-1 text-sm text-gray-600">
                            <div class="flex justify-between"><span>Caisse</span><span>@mru($latest->cash)</span></div>
                            <div class="flex justify-between"><span>Bankily</span><span>@mru($latest->bankily)</span></div>
                            <div class="flex justify-between"><span>Masrivi</span><span>@mru($latest->masrivi)</span></div>
                            <div class="flex justify-between"><span>Sedad</span><span>@mru($latest->sedad)</span></div>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-md">
                    Aucun relevé pour l'instant. Saisis ton premier relevé ci-dessous : c'est lui qui rend la page Trésorerie fiable.
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-1">Nouveau relevé</h3>
                <p class="text-sm text-gray-500 mb-4">
                    À faire une fois par semaine, toujours le même jour : compte l'argent du tiroir et regarde
                    le solde de chaque compte mobile. Deux minutes suffisent.
                </p>

                <form method="POST" action="{{ route('balances.store') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    @csrf
                    <div>
                        <x-input-label for="recorded_on" value="Date du relevé" />
                        <x-text-input id="recorded_on" name="recorded_on" type="date" class="mt-1 block w-full" :value="old('recorded_on', $today)" required />
                    </div>
                    <div>
                        <x-input-label for="cash" value="Caisse (espèces)" />
                        <x-text-input id="cash" name="cash" type="number" step="1" min="0" class="mt-1 block w-full" :value="old('cash', 0)" required />
                    </div>
                    <div>
                        <x-input-label for="bankily" value="Bankily" />
                        <x-text-input id="bankily" name="bankily" type="number" step="1" min="0" class="mt-1 block w-full" :value="old('bankily', 0)" required />
                    </div>
                    <div>
                        <x-input-label for="masrivi" value="Masrivi" />
                        <x-text-input id="masrivi" name="masrivi" type="number" step="1" min="0" class="mt-1 block w-full" :value="old('masrivi', 0)" required />
                    </div>
                    <div>
                        <x-input-label for="sedad" value="Sedad" />
                        <x-text-input id="sedad" name="sedad" type="number" step="1" min="0" class="mt-1 block w-full" :value="old('sedad', 0)" required />
                    </div>
                    <div>
                        <x-input-label for="note" value="Note (facultatif)" />
                        <x-text-input id="note" name="note" type="text" class="mt-1 block w-full" :value="old('note')" maxlength="255" />
                    </div>
                    <div class="sm:col-span-3">
                        <x-primary-button>Enregistrer le relevé</x-primary-button>
                        <span class="ms-3 text-xs text-gray-500">Un seul relevé par date : saisir la même date remplace le précédent.</span>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-4">Historique</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-3 py-2">Date</th>
                                <th class="px-3 py-2 text-right">Caisse</th>
                                <th class="px-3 py-2 text-right">Bankily</th>
                                <th class="px-3 py-2 text-right">Masrivi</th>
                                <th class="px-3 py-2 text-right">Sedad</th>
                                <th class="px-3 py-2 text-right">Total</th>
                                <th class="px-3 py-2">Saisi par</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($snapshots as $snapshot)
                                <tr>
                                    <td class="px-3 py-2 text-gray-600">{{ $snapshot->recorded_on->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2 text-right">@mru($snapshot->cash)</td>
                                    <td class="px-3 py-2 text-right">@mru($snapshot->bankily)</td>
                                    <td class="px-3 py-2 text-right">@mru($snapshot->masrivi)</td>
                                    <td class="px-3 py-2 text-right">@mru($snapshot->sedad)</td>
                                    <td class="px-3 py-2 text-right font-semibold text-brand-600">@mru($snapshot->total())</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $snapshot->user->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-3 py-6 text-center text-gray-500">Aucun relevé enregistré.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
