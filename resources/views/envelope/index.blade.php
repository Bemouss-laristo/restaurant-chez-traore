<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Enveloppe du mois — {{ $monthLabel }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('reports._nav')

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">{{ $errors->first() }}</div>
            @endif

            <form method="GET" class="bg-white shadow sm:rounded-lg p-3 flex flex-wrap items-center gap-2">
                <label class="text-sm text-gray-600">Mois :</label>
                <input type="month" name="month" value="{{ $report['month'] }}" class="border-gray-300 rounded-md shadow-sm" />
                <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Afficher</button>
            </form>

            @if ($report['opening'] <= 0)
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-md">
                    Aucune enveloppe fixée pour ce mois. Indique en bas de page l'argent de travail que tu mets en route (ex : 200 000 MRU).
                </div>
            @endif

            @if ($report['aheadOfSchedule'])
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                    ⚠ Tu consommes l'enveloppe plus vite que le temps ne passe :
                    <strong>{{ (int) $report['usedPercent'] }} %</strong> dépensés alors que le mois est à
                    <strong>{{ (int) $report['monthProgress'] }} %</strong>.
                </div>
            @endif

            {{-- Les 4 chiffres --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white shadow sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Enveloppe de départ</div>
                    <div class="text-2xl font-bold text-gray-800 mt-1">@mru($report['opening'])</div>
                </div>
                <div class="bg-white shadow sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Sorties du mois</div>
                    <div class="text-2xl font-bold text-red-600 mt-1">@mru($report['outflow'])</div>
                    <div class="mt-2 space-y-1 text-xs text-gray-500">
                        <div class="flex justify-between"><span>Dépenses payées</span><span>@mru($report['paidExpenses'])</span></div>
                        <div class="flex justify-between"><span>Factures fournisseurs</span><span>@mru($report['supplierPayments'])</span></div>
                    </div>
                </div>
                <div class="bg-white shadow sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Encaissements</div>
                    <div class="text-2xl font-bold text-green-600 mt-1">@mru($report['cashIn'])</div>
                    <div class="mt-2 text-xs text-gray-500">Les ventes reconstituent l'enveloppe.</div>
                </div>
                <div class="bg-white shadow sm:rounded-lg p-5 {{ $report['remaining'] < 0 ? 'bg-red-50' : '' }}">
                    <div class="text-sm text-gray-500">Reste dans l'enveloppe</div>
                    <div class="text-2xl font-bold mt-1 {{ $report['remaining'] < 0 ? 'text-red-700' : 'text-brand-600' }}">@mru($report['remaining'])</div>
                    <div class="mt-2 text-xs text-gray-500">Dont surplus mobilisable : <span class="font-medium text-gray-700">@mru($report['surplus'])</span></div>
                </div>
            </div>

            {{-- Vitesse --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-1">Vitesse de consommation</h3>
                <p class="text-sm text-gray-500 mb-4">La barre des dépenses doit rester derrière celle du temps. Si elle passe devant, tu finiras le mois à court.</p>

                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Temps écoulé</span>
                            <span class="text-gray-500">{{ $report['daysElapsed'] }} / {{ $report['daysInMonth'] }} jours — {{ (int) $report['monthProgress'] }} %</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full" style="height:10px">
                            <div class="rounded-full bg-gray-500" style="height:10px;width: {{ (int) $report['monthProgress'] }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Enveloppe consommée</span>
                            <span class="{{ $report['aheadOfSchedule'] ? 'text-red-700 font-medium' : 'text-gray-500' }}">{{ (int) $report['usedPercent'] }} %</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full" style="height:10px">
                            <div class="rounded-full {{ $report['aheadOfSchedule'] ? 'bg-red-600' : 'bg-green-600' }}" style="height:10px;width: {{ min(100, (int) $report['usedPercent']) }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-5">
                    <div class="bg-gray-50 rounded-md p-4">
                        <div class="text-xs text-gray-500">Dépense moyenne par jour</div>
                        <div class="text-lg font-semibold text-gray-800">@mru($report['burnPerDay'])</div>
                    </div>
                    <div class="bg-gray-50 rounded-md p-4">
                        <div class="text-xs text-gray-500">Projection de fin de mois</div>
                        <div class="text-lg font-semibold {{ $report['opening'] > 0 && $report['projection'] > $report['opening'] ? 'text-red-700' : 'text-gray-800' }}">@mru($report['projection'])</div>
                    </div>
                    <div class="bg-gray-50 rounded-md p-4">
                        <div class="text-xs text-gray-500">Le reste tient encore</div>
                        <div class="text-lg font-semibold text-gray-800">
                            {{ $report['daysCovered'] === null ? '—' : $report['daysCovered'].' jour(s)' }}
                        </div>
                        @if ($report['runsOutOn'])
                            <div class="text-xs text-gray-400 mt-1">Jusqu'au {{ $report['runsOutOn']->format('d/m') }}</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Bénéfice et réserve --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-1">Bénéfice et réserve</h3>
                    <p class="text-sm text-gray-500 mb-4">Le bénéfice compte toutes les charges du mois, y compris la marchandise prise à crédit et pas encore payée.</p>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Bénéfice du mois</span>
                            <span class="text-xl font-bold {{ $report['profit'] < 0 ? 'text-red-700' : 'text-green-700' }}">@mru($report['profit'])</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Mis de côté ce mois</span>
                            <span class="text-lg font-semibold text-brand-600">@mru($report['setAside'])</span>
                        </div>
                        <div class="flex justify-between items-center border-t border-gray-200 pt-3">
                            <span class="text-gray-800 font-medium">Réserve totale (tous mois)</span>
                            <span class="text-xl font-bold text-brand-700">@mru($report['reserveTotal'])</span>
                        </div>
                    </div>
                    @if ($report['surplus'] > 0)
                        <p class="mt-4 text-sm text-green-700">
                            Tu peux mettre de côté jusqu'à <strong>{{ number_format($report['surplus'], 0, ',', ' ') }} MRU</strong> sans toucher à l'argent de travail du mois prochain.
                        </p>
                    @endif
                </div>

                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-4">Mettre de l'argent de côté</h3>
                    <form method="POST" action="{{ route('envelope.reserve') }}" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="direction" value="Opération" />
                                <select id="direction" name="direction" class="mt-1 block w-full border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm">
                                    <option value="set_aside">Mettre de côté</option>
                                    <option value="take_back">Reprendre de la réserve</option>
                                </select>
                            </div>
                            <div>
                                <x-input-label for="amount" value="Montant (MRU)" />
                                <x-text-input id="amount" name="amount" type="number" step="1" min="1" class="mt-1 block w-full" :value="old('amount')" required />
                            </div>
                            <div>
                                <x-input-label for="payment_method" value="D'où sort l'argent" />
                                <select id="payment_method" name="payment_method" class="mt-1 block w-full border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm">
                                    @foreach ($paymentMethods as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="moved_on" value="Date" />
                                <x-text-input id="moved_on" name="moved_on" type="date" class="mt-1 block w-full" :value="old('moved_on', $today)" required />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="note" value="Note (facultatif)" />
                            <x-text-input id="note" name="note" type="text" class="mt-1 block w-full" :value="old('note')" placeholder="Ex : épargne travaux" maxlength="255" />
                        </div>
                        <x-primary-button>Enregistrer</x-primary-button>
                        <p class="text-xs text-gray-500">En espèces, le montant sort de la caisse ouverte. Ce n'est pas une dépense : l'argent reste à toi.</p>
                    </form>
                </div>
            </div>

            {{-- Mouvements de réserve --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-4">Mouvements de réserve du mois</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-3 py-2">Date</th>
                                <th class="px-3 py-2">Opération</th>
                                <th class="px-3 py-2">Moyen</th>
                                <th class="px-3 py-2">Par</th>
                                <th class="px-3 py-2">Note</th>
                                <th class="px-3 py-2 text-right">Montant</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($report['movements'] as $movement)
                                <tr>
                                    <td class="px-3 py-2 text-gray-600">{{ $movement->moved_on->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $movement->isWithdrawal() ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800' }}">
                                            {{ $movement->isWithdrawal() ? 'Repris' : 'Mis de côté' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-gray-600">{{ $movement->payment_method->label() }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $movement->user->name ?? '—' }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $movement->note ?: '—' }}</td>
                                    <td class="px-3 py-2 text-right font-medium {{ $movement->isWithdrawal() ? 'text-amber-700' : 'text-brand-700' }}">@mru($movement->amount)</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">Aucun mouvement ce mois.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Dotation --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-1">Fixer l'enveloppe du mois</h3>
                <p class="text-sm text-gray-500 mb-4">L'argent de travail que tu mets en route au début du mois. Repère : la moyenne des sorties des 2 ou 3 derniers mois, plus 10 %.</p>
                <form method="POST" action="{{ route('envelope.store') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    @csrf
                    <input type="hidden" name="month" value="{{ $report['month'] }}" />
                    <div>
                        <x-input-label for="opening_amount" value="Montant de l'enveloppe (MRU)" />
                        <x-text-input id="opening_amount" name="opening_amount" type="number" step="1000" min="0" class="mt-1 block w-full"
                            :value="old('opening_amount', (int) $report['opening'])" required />
                    </div>
                    <div>
                        <x-input-label for="note" value="Note (facultatif)" />
                        <x-text-input id="note" name="note" type="text" class="mt-1 block w-full" :value="old('note', $report['plan']->note ?? '')" maxlength="255" />
                    </div>
                    <div>
                        <x-primary-button>Enregistrer l'enveloppe</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
