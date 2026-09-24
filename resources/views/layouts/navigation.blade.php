{{--
    Barre latérale de l'espace de gestion.

    Le menu est décrit une seule fois (liste ci-dessous) puis affiché en boucle :
    ajouter une rubrique = ajouter une ligne. Chaque rôle ne voit que ce qui le
    concerne ; les autorisations réelles restent vérifiées côté serveur par les routes.
--}}
@php
    $user = auth()->user();
    $manages = $user->isAdmin() || $user->isGerant();
    $pendingOrders = \App\Models\Order::pending()->count();

    // [libellé, route, motifs de page active, icône, pastille]
    $sections = [
        ['title' => null, 'items' => [
            ['Tableau de bord', 'dashboard', ['dashboard'], 'home', null],
        ]],
        ['title' => 'Service', 'items' => array_values(array_filter([
            ['Vente', 'sales.create', ['sales.*'], 'cart', null],
            ['Caisse', 'caisse.index', ['caisse.*'], 'cash', null],
            $user->isCaissier() ? ['Mes dépenses', 'cashier-expenses.index', ['cashier-expenses.*'], 'wallet', null] : null,
            ['Commandes', 'orders.index', ['orders.*'], 'orders', $pendingOrders ?: null],
        ]))],
    ];

    if ($manages) {
        $sections[] = ['title' => 'Stock', 'items' => [
            ['Produits', 'products.index', ['products.*'], 'tag', null],
            ['Stock', 'stock-items.index', ['stock-items.*'], 'cube', null],
            ['Achats', 'purchases.create', ['purchases.*'], 'truck', null],
            ['Fournisseurs', 'suppliers.index', ['suppliers.*'], 'store', null],
        ]];
        $sections[] = ['title' => 'Argent', 'items' => [
            ['Dépenses', 'expenses.index', ['expenses.*'], 'receipt', null],
            ['Salaires', 'staff.index', ['staff.*'], 'users', null],
            ['Rapports', 'reports.daily', ['reports.*', 'envelope.*', 'balances.*', 'reconciliation.*'], 'chart', null],
        ]];
    }

    if ($user->isAdmin()) {
        $sections[] = ['title' => 'Administration', 'items' => [
            ['Employés', 'admin.users.index', ['admin.users.*'], 'user-group', null],
        ]];
    }

    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $roleStyle = match ($user->role->value) {
        'admin' => 'bg-brand-500/20 text-brand-200 ring-brand-400/30',
        'gerant' => 'bg-sky-400/15 text-sky-200 ring-sky-300/30',
        default => 'bg-emerald-400/15 text-emerald-200 ring-emerald-300/30',
    };
@endphp

{{-- Voile derrière le menu, sur téléphone --}}
<div x-show="sidebarOpen" x-transition.opacity.duration.200ms x-on:click="sidebarOpen = false"
    class="fixed inset-0 z-30 bg-cocoa-950/60 backdrop-blur-sm lg:hidden" style="display: none;" aria-hidden="true"></div>

<aside
    class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-cocoa-900 text-cocoa-100 transition-transform duration-300 ease-out lg:translate-x-0"
    x-bind:class="{ '!translate-x-0 shadow-2xl': sidebarOpen }"
    aria-label="Menu principal">

    {{-- Enseigne --}}
    <div class="flex items-center gap-3 px-5 pb-4 pt-5">
        <a href="{{ route('dashboard') }}" class="group flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 text-white shadow-lg shadow-brand-900/40 transition-transform duration-300 group-hover:-rotate-6 group-hover:scale-105">
                <x-icon name="fire" class="h-6 w-6" />
            </span>
            <span class="leading-tight">
                <span class="block text-base font-bold text-white">Chez <span class="text-brand-300">Traoré</span></span>
                <span class="block text-[11px] uppercase tracking-[0.18em] text-cocoa-400">Gestion</span>
            </span>
        </a>
        <button type="button" x-on:click="sidebarOpen = false"
            class="ms-auto rounded-lg p-1.5 text-cocoa-300 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Fermer le menu">
            <x-icon name="close" class="h-5 w-5" />
        </button>
    </div>

    {{-- Rubriques --}}
    <nav class="side-scroll flex-1 overflow-y-auto px-3 pb-4">
        @foreach ($sections as $section)
            @if ($section['title'])
                <div class="side-section">{{ $section['title'] }}</div>
            @endif
            <ul class="space-y-0.5">
                @foreach ($section['items'] as [$label, $route, $patterns, $icon, $badge])
                    @php($active = request()->routeIs(...$patterns))
                    <li>
                        <a href="{{ route($route) }}" @class(['side-link', 'is-active' => $active])
                            @if ($active) aria-current="page" @endif>
                            <x-icon :name="$icon" class="side-icon" />
                            <span class="flex-1 truncate">{{ $label }}</span>
                            @if ($badge)
                                <span class="inline-flex min-w-[1.35rem] animate-soft-pulse items-center justify-center rounded-full bg-brand-500 px-1.5 text-xs font-bold text-white">
                                    {{ $badge }}
                                </span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>

    {{-- Utilisateur connecté --}}
    <div class="border-t border-white/10 p-3">
        @include('partials.install-app', ['variant' => 'sidebar'])
        <div class="flex items-center gap-3 rounded-xl bg-white/5 p-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-300 to-brand-600 text-sm font-bold text-white ring-2 ring-white/10">
                {{ $initials ?: '?' }}
            </span>
            <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-semibold text-white">{{ $user->name }}</div>
                <span class="mt-0.5 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $roleStyle }}">
                    {{ $user->role->label() }}
                </span>
            </div>
        </div>
        <div class="mt-2 grid grid-cols-2 gap-2">
            <a href="{{ route('profile.edit') }}"
                class="flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-medium text-cocoa-200 transition hover:bg-white/10 hover:text-white">
                <x-icon name="user" class="h-4 w-4" /> Profil
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="flex w-full items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-medium text-cocoa-200 transition hover:bg-red-500/15 hover:text-red-200">
                    <x-icon name="logout" class="h-4 w-4" /> Déconnexion
                </button>
            </form>
        </div>
    </div>
</aside>
