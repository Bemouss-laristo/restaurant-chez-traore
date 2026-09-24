<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $supplier->name }} — relevé de {{ $monthLabel }}</h2>
            <a href="{{ route('suppliers.index') }}" class="text-sm text-brand-600 hover:underline">← Tous les fournisseurs</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">{{ session('error') }}</div>
            @endif
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">{{ $errors->first() }}</div>
            @endif

            <form method="GET" class="bg-white shadow sm:rounded-lg p-3 flex flex-wrap items-center gap-2">
                <label class="text-sm text-gray-600">Mois :</label>
                <input type="month" name="month" value="{{ $month }}" class="border-gray-300 rounded-md shadow-sm" />
                <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Afficher</button>
            </form>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Pris à crédit ce mois</div><div class="text-2xl font-bold text-gray-800 mt-1">@mru($monthCredit)</div></div>
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Payé ce mois</div><div class="text-2xl font-bold text-green-700 mt-1">@mru($monthPaid)</div></div>
                @if ($balance < 0)
                    <div class="bg-white shadow sm:rounded-lg p-5">
                        <div class="text-sm text-gray-500">Avance disponible</div>
                        <div class="text-2xl font-bold text-green-700 mt-1">@mru(abs($balance))</div>
                        <div class="text-xs text-gray-500 mt-1">Déjà payé d'avance : il te doit encore cette valeur en marchandise.</div>
                    </div>
                @else
                    <div class="bg-white shadow sm:rounded-lg p-5">
                        <div class="text-sm text-gray-500">À payer (total dû)</div>
                        <div class="text-2xl font-bold text-red-700 mt-1">@mru($balance)</div>
                    </div>
                @endif
            </div>

            @if ($balance < 0)
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md text-sm">
                    <strong>Abonnement payé d'avance.</strong>
                    L'argent est déjà sorti ; chaque livraison saisie vient manger cette avance.
                    Quand elle tombe à zéro, c'est que le mois payé est consommé : si des livraisons
                    continuent, elles redeviennent une dette.
                </div>
            @endif

            @if ($items->isNotEmpty())
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-1">Articles rattachés à ce fournisseur</h3>
                    <p class="text-sm text-gray-500 mb-3">
                        Chaque entrée de ces articles dans le menu Achats atterrit automatiquement sur ce relevé.
                    </p>
                    <div class="space-y-2">
                        @foreach ($items as $item)
                            <div class="flex flex-wrap items-center justify-between gap-2 border border-gray-200 rounded-md p-3 text-sm">
                                <div>
                                    <a href="{{ route('stock-items.edit', $item) }}" class="font-medium text-brand-600 hover:underline">{{ $item->name }}</a>
                                    <span class="text-gray-600">
                                        — en stock : {{ rtrim(rtrim(number_format((float) $item->quantity, 3, ',', ' '), '0'), ',') }} {{ $item->unit->value }}
                                    </span>
                                    @if ($item->hasAgreedPrice())
                                        <span class="text-gray-600">— prix convenu : @mru($item->agreed_unit_price) le {{ $item->unit->value }}</span>
                                    @endif
                                    @if ((float) $item->daily_quantity > 0)
                                        <span class="text-gray-600">
                                            — habituellement {{ rtrim(rtrim(number_format((float) $item->daily_quantity, 3, ',', ' '), '0'), ',') }} {{ $item->unit->value }} par jour
                                        </span>
                                    @endif
                                </div>
                                @if (($item->default_payment_method?->value ?? null) === 'credit')
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">pris à crédit</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($byItem->isNotEmpty())
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-1">Total pris ce mois</h3>
                    <p class="text-sm text-gray-500 mb-4">C'est ce qu'il doit facturer. Un écart avec sa facture = une prise non saisie, ou une facture gonflée.</p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="px-3 py-2">Article</th>
                                    <th class="px-3 py-2 text-right">Quantité prise</th>
                                    <th class="px-3 py-2 text-right">Jours de prise</th>
                                    <th class="px-3 py-2 text-right">Montant</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($byItem as $row)
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-gray-900">{{ $row['name'] }}</td>
                                        <td class="px-3 py-2 text-right">{{ rtrim(rtrim(number_format($row['quantity'], 3, ',', ' '), '0'), ',') }} {{ $row['unit'] }}</td>
                                        <td class="px-3 py-2 text-right text-gray-500">{{ $row['days'] }}</td>
                                        <td class="px-3 py-2 text-right font-semibold">@mru($row['amount'])</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td class="px-3 py-2 font-medium text-gray-900">Total</td>
                                    <td class="px-3 py-2"></td>
                                    <td class="px-3 py-2"></td>
                                    <td class="px-3 py-2 text-right font-bold text-red-700">@mru($byItem->sum('amount'))</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-1">Détail jour par jour</h3>
                <p class="text-sm text-gray-500 mb-4">Chaque prise enregistrée, dans l'ordre des dates.</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-3 py-2">Date</th>
                                <th class="px-3 py-2">Article</th>
                                <th class="px-3 py-2 text-right">Quantité</th>
                                <th class="px-3 py-2 text-right">Prix unitaire</th>
                                <th class="px-3 py-2 text-right">Montant</th>
                                <th class="px-3 py-2">Saisi par</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($takings as $row)
                                <tr>
                                    <td class="px-3 py-2 text-gray-600">{{ $row['date']->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2 text-gray-900">{{ $row['item'] }}</td>
                                    <td class="px-3 py-2 text-right">{{ rtrim(rtrim(number_format($row['quantity'], 3, ',', ' '), '0'), ',') }} {{ $row['unit'] }}</td>
                                    <td class="px-3 py-2 text-right text-gray-500">@mru($row['unitPrice'])</td>
                                    <td class="px-3 py-2 text-right font-medium">@mru($row['amount'])</td>
                                    <td class="px-3 py-2 text-gray-600">
                                        {{ $row['user'] }}
                                        @unless ($row['credit'])
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">payé</span>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">Aucune prise enregistrée ce mois.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-1">Les saisies, telles qu'enregistrées</h3>
                <p class="text-sm text-gray-500 mb-4">Utile si une ligne du détail te semble fausse : tu retrouves ici la saisie d'origine.</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-3 py-2">Date</th>
                                <th class="px-3 py-2">Détail</th>
                                <th class="px-3 py-2">Saisi par</th>
                                <th class="px-3 py-2">Règlement</th>
                                <th class="px-3 py-2 text-right">Montant</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($deliveries as $delivery)
                                <tr>
                                    <td class="px-3 py-2 text-gray-600">{{ $delivery->spent_at->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2 text-gray-700">{!! nl2br(e($delivery->description)) !!}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $delivery->user->name ?? '—' }}</td>
                                    <td class="px-3 py-2">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $delivery->isCredit() ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800' }}">
                                            {{ $delivery->isCredit() ? 'À crédit' : $delivery->payment_method->label() }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-right font-medium">@mru($delivery->amount)</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-3 py-6 text-center text-gray-500">Aucune livraison ce mois.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-4">Règlements du mois</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-3 py-2">Date</th>
                                <th class="px-3 py-2">Moyen</th>
                                <th class="px-3 py-2">Par</th>
                                <th class="px-3 py-2">Note</th>
                                <th class="px-3 py-2 text-right">Montant</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($payments as $payment)
                                <tr>
                                    <td class="px-3 py-2 text-gray-600">{{ $payment->paid_at->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2">{{ $payment->payment_method->label() }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $payment->user->name ?? '—' }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $payment->note ?: '—' }}</td>
                                    <td class="px-3 py-2 text-right font-medium text-green-700">@mru($payment->amount)</td>
                                    <td class="px-3 py-2 text-right">
                                        <form method="POST" action="{{ route('suppliers.payments.destroy', [$supplier, $payment]) }}"
                                            onsubmit="return confirm('Supprimer ce règlement ? L\'argent revient en caisse et la dette remonte.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-xs text-red-600 hover:underline">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">Aucun règlement ce mois.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-1">Payer ce fournisseur</h3>
                <p class="text-sm text-gray-500 mb-2">L'argent sort de la caisse (ou du compte mobile) et la dette baisse d'autant. Ce n'est pas une nouvelle dépense : la marchandise a déjà été comptée à la livraison.</p>
                @php
                    $daily = $items->filter(fn ($i) => $i->dailyTotal() !== null);
                    $days = \Illuminate\Support\Carbon::parse($month.'-01')->daysInMonth;
                    $perDay = $daily->sum(fn ($i) => (float) $i->dailyTotal());
                    $monthly = $perDay * $days;
                @endphp
                @if ($monthly > 0)
                    <p class="text-sm text-gray-600 mb-4">
                        <strong>Si tu paies le mois d'avance :</strong>
                        {{ $daily->pluck('name')->implode(', ') }} —
                        @mru($perDay) par jour × {{ $days }} jours = <strong>@mru($monthly)</strong>.
                        Le solde passera en avance, que les prises quotidiennes viendront consommer.
                    </p>
                @endif
                <form method="POST" action="{{ route('suppliers.pay', $supplier) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                    @csrf
                    <div>
                        <x-input-label for="amount" value="Montant payé (MRU)" />
                        <x-text-input id="amount" name="amount" type="number" step="1" min="1" class="mt-1 block w-full"
                            :value="old('amount', $balance > 0 ? $balance : ($monthly > 0 ? $monthly : 0))" required />
                        <p class="mt-1 text-xs text-gray-500">Le total pris ce mois est repris plus haut : compare-le à sa facture avant de payer.</p>
                    </div>
                    <div>
                        <x-input-label for="payment_method" value="Moyen de paiement" />
                        <select id="payment_method" name="payment_method" class="mt-1 block w-full border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm">
                            @foreach ($paymentMethods as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="paid_at" value="Date" />
                        <x-text-input id="paid_at" name="paid_at" type="date" class="mt-1 block w-full" :value="old('paid_at', \App\Support\BusinessDay::today())" required />
                    </div>
                    <div>
                        <x-primary-button>Enregistrer le règlement</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
