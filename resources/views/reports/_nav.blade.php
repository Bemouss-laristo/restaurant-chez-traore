@php
    // [libellé, route, motif actif, icône]
    $tabs = [
        ['Journalier', 'reports.daily', 'reports.daily', 'calendar'],
        ['Hebdomadaire', 'reports.weekly', 'reports.weekly', 'calendar'],
        ['Mensuel', 'reports.monthly', 'reports.monthly', 'chart'],
        ['Achats', 'reports.purchases', 'reports.purchases', 'truck'],
        ['Enveloppe du mois', 'envelope.index', 'envelope.*', 'wallet'],
        ['Soldes', 'balances.index', 'balances.*', 'scale'],
        ['Trésorerie', 'reports.treasury', 'reports.treasury', 'cash'],
        ['Contrôle matière', 'reports.material', 'reports.material', 'fire'],
        ['Contrôle stock', 'reports.stock', 'reports.stock', 'cube'],
        ['Réconciliation', 'reconciliation.index', 'reconciliation.*', 'check'],
    ];
@endphp

{{-- Onglets : défilent horizontalement sur téléphone au lieu de s'empiler. --}}
<nav class="bg-white shadow sm:rounded-lg p-2" aria-label="Rapports">
    <div class="side-scroll flex gap-1 overflow-x-auto">
        @foreach ($tabs as [$label, $route, $pattern, $icon])
            @php($active = request()->routeIs($pattern))
            <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
                @class([
                    'inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg px-3.5 py-2 text-sm transition-all duration-200',
                    'bg-brand-600 font-semibold text-white shadow-sm' => $active,
                    'font-medium text-cocoa-700 hover:bg-brand-50 hover:text-brand-700' => ! $active,
                ])>
                <x-icon :name="$icon" class="h-4 w-4" />
                {{ $label }}
            </a>
        @endforeach
    </div>
</nav>
