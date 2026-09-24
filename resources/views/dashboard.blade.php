<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold leading-tight text-cocoa-900">Tableau de bord</h2>
        <p class="text-xs text-cocoa-500">Journée du {{ $businessDate->locale('fr')->translatedFormat('l d F Y') }} · ventes comptées jusqu'à 5 h</p>
    </x-slot>

    @php
        $user = auth()->user();
        $firstName = \Illuminate\Support\Str::of($user->name)->explode(' ')->first();
        $hour = (int) now()->format('G');
        $greeting = $hour >= 17 || $hour < 5 ? 'Bonsoir' : 'Bonjour';
        $maxRevenue = max(1, (float) $monthProducts->max('revenue'));
    @endphp

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Accueil --}}
            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-cocoa-800 via-cocoa-900 to-brand-900 p-6 text-white shadow-lift sm:p-8">
                {{-- Décor : deux halos chauds, purement visuels --}}
                <div class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-brand-500/30 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-24 right-40 h-56 w-56 rounded-full bg-brand-300/10 blur-3xl"></div>

                <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-brand-100 ring-1 ring-inset ring-white/10">
                            <x-icon name="moon" class="h-4 w-4" /> Service de 19 h à 2 h
                        </p>
                        <h1 class="mt-3 text-2xl font-bold sm:text-3xl">{{ $greeting }}, {{ $firstName }}</h1>
                        <p class="mt-1 text-sm text-cocoa-200">
                            {{ $user->role->label() }} · {{ $businessDate->locale('fr')->translatedFormat('l d F') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('sales.create') }}"
                            class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-900/30 transition hover:-translate-y-0.5 hover:bg-brand-400">
                            <x-icon name="cart" class="h-5 w-5" /> Nouvelle vente
                        </a>
                        <a href="{{ route('caisse.index') }}"
                            class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-inset ring-white/15 transition hover:-translate-y-0.5 hover:bg-white/20">
                            <x-icon name="cash" class="h-5 w-5" /> Caisse
                        </a>
                        @if ($canSeeFinance)
                            <a href="{{ route('purchases.create') }}"
                                class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-inset ring-white/15 transition hover:-translate-y-0.5 hover:bg-white/20">
                                <x-icon name="truck" class="h-5 w-5" /> Prise du jour
                            </a>
                            <a href="{{ route('reports.daily') }}"
                                class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-inset ring-white/15 transition hover:-translate-y-0.5 hover:bg-white/20">
                                <x-icon name="chart" class="h-5 w-5" /> Rapports
                            </a>
                        @endif
                    </div>
                </div>
            </section>

            {{-- Alertes --}}
            @if (count($alerts))
                <div class="space-y-2">
                    @foreach ($alerts as $alert)
                        @php($danger = $alert['level'] === 'danger')
                        <div class="flex items-start gap-3 rounded-xl border px-4 py-3 text-sm {{ $danger ? 'border-red-200 bg-red-50 text-red-800' : 'border-amber-200 bg-amber-50 text-amber-900' }}">
                            <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0 {{ $danger ? 'text-red-500' : 'text-amber-500' }}" />
                            <span>{{ $alert['message'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Chiffres du jour --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="kpi-card">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="text-sm font-medium text-cocoa-500">Ventes du jour</div>
                            <div class="mt-2 text-2xl font-bold text-cocoa-900"
                                x-data="countUp({{ (int) round($today['sales']) }}, ' MRU')" x-text="display">@mru($today['sales'])</div>
                        </div>
                        <span class="kpi-icon bg-emerald-50 text-emerald-600"><x-icon name="trend-up" class="h-6 w-6" /></span>
                    </div>
                    <div class="mt-3 text-xs text-cocoa-500">Hors ventes annulées</div>
                </div>

                <div class="kpi-card">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="text-sm font-medium text-cocoa-500">Commandes du jour</div>
                            <div class="mt-2 text-2xl font-bold text-cocoa-900"
                                x-data="countUp({{ (int) $today['orders'] }})" x-text="display">{{ $today['orders'] }}</div>
                        </div>
                        <span class="kpi-icon bg-sky-50 text-sky-600"><x-icon name="orders" class="h-6 w-6" /></span>
                    </div>
                    <div class="mt-3 text-xs text-cocoa-500">
                        @if ($today['orders'] > 0)
                            Panier moyen : @mru($today['sales'] / $today['orders'])
                        @else
                            Aucune vente pour l'instant
                        @endif
                    </div>
                </div>

                @if ($canSeeFinance)
                    <a href="{{ route('expenses.index', ['date' => $businessDate->toDateString()]) }}" class="kpi-card group block">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="text-sm font-medium text-cocoa-500">Dépenses du jour</div>
                                <div class="mt-2 text-2xl font-bold text-red-600"
                                    x-data="countUp({{ (int) round($today['expenses']) }}, ' MRU')" x-text="display">@mru($today['expenses'])</div>
                            </div>
                            <span class="kpi-icon bg-red-50 text-red-500"><x-icon name="receipt" class="h-6 w-6" /></span>
                        </div>
                        <div class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600">
                            Voir le détail (caissiers inclus)
                            <x-icon name="arrow-right" class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
                        </div>
                    </a>

                    <div class="kpi-card">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="text-sm font-medium text-cocoa-500">Bénéfice du jour</div>
                                <div class="mt-2 text-2xl font-bold {{ $today['profit'] < 0 ? 'text-red-600' : 'text-brand-600' }}"
                                    x-data="countUp({{ (int) round($today['profit']) }}, ' MRU')" x-text="display">@mru($today['profit'])</div>
                            </div>
                            <span class="kpi-icon {{ $today['profit'] < 0 ? 'bg-red-50 text-red-500' : 'bg-brand-50 text-brand-600' }}">
                                <x-icon :name="$today['profit'] < 0 ? 'trend-down' : 'sparkles'" class="h-6 w-6" />
                            </span>
                        </div>
                        <div class="mt-3 text-xs text-cocoa-500">Ventes moins dépenses du jour</div>
                    </div>
                @else
                    <div class="kpi-card sm:col-span-2">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="text-sm font-medium text-cocoa-500">Solde de caisse</div>
                                <div class="mt-2 text-2xl font-bold text-brand-600">
                                    @if ($today['hasOpenSession'])
                                        <span x-data="countUp({{ (int) round($today['cashBalance']) }}, ' MRU')" x-text="display">@mru($today['cashBalance'])</span>
                                    @else
                                        <span class="text-lg text-cocoa-400">Caisse fermée</span>
                                    @endif
                                </div>
                            </div>
                            <span class="kpi-icon bg-brand-50 text-brand-600">
                                <x-icon :name="$today['hasOpenSession'] ? 'lock-open' : 'lock'" class="h-6 w-6" />
                            </span>
                        </div>
                        <a href="{{ route('caisse.index') }}" class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:underline">
                            {{ $today['hasOpenSession'] ? 'Gérer la caisse' : 'Ouvrir la caisse' }}
                            <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                        </a>
                    </div>
                @endif
            </div>

            @if ($canSeeFinance)
                {{-- Solde de caisse --}}
                <div class="kpi-card flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="kpi-icon bg-brand-50 text-brand-600">
                            <x-icon :name="$today['hasOpenSession'] ? 'lock-open' : 'lock'" class="h-6 w-6" />
                        </span>
                        <div>
                            <div class="text-sm font-medium text-cocoa-500">Solde de caisse (théorique)</div>
                            <div class="text-2xl font-bold text-cocoa-900">
                                @if ($today['hasOpenSession'])
                                    <span x-data="countUp({{ (int) round($today['cashBalance']) }}, ' MRU')" x-text="display">@mru($today['cashBalance'])</span>
                                @else
                                    <span class="text-lg font-semibold text-cocoa-400">Aucune caisse ouverte</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('caisse.index') }}"
                        class="inline-flex items-center gap-2 rounded-xl border border-cocoa-200 bg-white px-4 py-2 text-sm font-semibold text-cocoa-800 transition hover:border-brand-300 hover:bg-brand-50">
                        Gérer la caisse <x-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
                    {{-- Tous les produits vendus ce mois --}}
                    <div class="bg-white shadow sm:rounded-lg p-6 lg:col-span-3">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 font-semibold text-cocoa-900">
                                <x-icon name="fire" class="h-5 w-5 text-brand-500" /> Produits vendus ce mois
                            </h3>
                            <a href="{{ route('reports.material') }}" class="text-sm font-medium text-brand-600 hover:underline">Contrôle matière →</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left">
                                        <th class="px-2 py-2">Produit</th>
                                        <th class="px-2 py-2 text-right">Qté</th>
                                        <th class="px-2 py-2 text-right">CA</th>
                                        <th class="px-2 py-2 text-right">Marge</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-cocoa-100">
                                    @forelse ($monthProducts as $p)
                                        <tr>
                                            <td class="px-2 py-2.5">
                                                <div class="font-medium text-cocoa-900">{{ $p->name }}</div>
                                                {{-- Barre : part du meilleur produit du mois --}}
                                                <div class="mt-1.5 h-1.5 w-full max-w-[12rem] overflow-hidden rounded-full bg-cocoa-100">
                                                    <div class="h-full rounded-full bg-gradient-to-r from-brand-300 to-brand-500 transition-all duration-700"
                                                        style="width: {{ round(((float) $p->revenue / $maxRevenue) * 100) }}%"></div>
                                                </div>
                                            </td>
                                            <td class="px-2 py-2.5 text-right text-cocoa-600">{{ (int) $p->qty }}</td>
                                            <td class="px-2 py-2.5 text-right font-semibold text-emerald-700">@mru($p->revenue)</td>
                                            <td class="px-2 py-2.5 text-right text-brand-600">@mru($p->margin)</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-2 py-8 text-center text-cocoa-500">
                                                <x-icon name="sparkles" class="mx-auto mb-2 h-8 w-8 text-cocoa-300" />
                                                Pas encore de ventes ce mois.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Stock faible --}}
                    <div class="bg-white shadow sm:rounded-lg p-6 lg:col-span-2">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="flex items-center gap-2 font-semibold text-cocoa-900">
                                <x-icon name="cube" class="h-5 w-5 text-brand-500" /> Stock faible
                            </h3>
                            <a href="{{ route('stock-items.index') }}" class="text-sm font-medium text-brand-600 hover:underline">Voir tout →</a>
                        </div>
                        <div class="space-y-3">
                            @forelse ($lowStock as $item)
                                @php($ratio = (float) $item->alert_threshold > 0 ? min(100, ((float) $item->quantity / (float) $item->alert_threshold) * 100) : 0)
                                <div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-medium text-cocoa-900">{{ $item->name }}</span>
                                        <span class="text-red-600">
                                            {{ (float) $item->quantity }} {{ $item->unit->value }}
                                            <span class="text-cocoa-400">/ seuil {{ (float) $item->alert_threshold }}</span>
                                        </span>
                                    </div>
                                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-red-100">
                                        <div class="h-full rounded-full bg-red-500" style="width: {{ max(4, round($ratio)) }}%"></div>
                                    </div>
                                </div>
                            @empty
                                <div class="flex flex-col items-center py-6 text-center text-sm text-cocoa-500">
                                    <span class="mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                        <x-icon name="check" class="h-7 w-7" />
                                    </span>
                                    Aucun article en alerte. Tout va bien.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
