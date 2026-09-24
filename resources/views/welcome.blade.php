<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chez Traoré — Restaurant à Nouakchott</title>
    <meta name="description" content="Chez Traoré — grillades, pizzas, tacos, kebabs et desserts à Nouakchott. Commandez en ligne, à emporter.">
    <meta name="theme-color" content="#1c1411">
    {{-- Active les apparitions au défilement seulement si le JavaScript tourne :
         sans lui, tout le contenu reste visible. --}}
    <script>document.documentElement.classList.add('js');</script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @include('partials.pwa-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Icônes de marque (WhatsApp) fournies par la bibliothèque Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="site bg-cream font-sans text-cocoa-900 antialiased">
    @php
        $words = ['Le', 'goût', 'qui', 'rassemble'];
        $gallery = $featured->take(3)->values();
        $dishCount = \App\Models\Product::active()->count();
        $wa = 'https://wa.me/'.config('restaurant.phone_international');
        $specialties = ['Grillades', 'Pizzas', 'Tacos', 'Kebabs', 'Chawarma', 'Poulet braisé', 'Burgers', 'Poissons', 'Desserts', 'Milk-shakes', 'Jus frais'];
        $payments = ['Espèces', 'Bankily', 'Masrivi', 'Sedad'];
    @endphp

    {{-- ============ Barre de navigation : transparente sur le héro, pleine au défilement ============ --}}
    <header x-data="{ scrolled: false }" x-init="scrolled = window.scrollY > 40"
        x-on:scroll.window.passive="scrolled = window.scrollY > 40"
        class="fixed inset-x-0 top-0 z-40 transition-all duration-500"
        :class="scrolled ? 'bg-cocoa-950/85 py-2 shadow-lg shadow-black/20 backdrop-blur-md' : 'bg-transparent py-4'">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4">
            <a href="/" class="group flex items-center gap-2.5">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 text-white shadow-lg shadow-brand-900/40 transition-transform duration-300 group-hover:-rotate-12 group-hover:scale-110">
                    <x-icon name="fire" class="h-6 w-6" />
                </span>
                <span class="text-lg font-bold text-white">Chez <span class="text-brand-300">Traoré</span></span>
            </a>

            <nav class="hidden items-center gap-1 md:flex" aria-label="Navigation">
                @foreach ([['Spécialités', '#menu'], ['Comment commander', '#etapes'], ['Infos', '#infos']] as [$label, $href])
                    <a href="{{ $href }}" class="group relative rounded-lg px-3 py-2 text-sm font-medium text-cocoa-100 transition hover:text-white">
                        {{ $label }}
                        <span class="absolute inset-x-3 -bottom-0.5 h-0.5 origin-left scale-x-0 rounded-full bg-brand-400 transition-transform duration-300 group-hover:scale-x-100"></span>
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
            @include('partials.install-app', ['variant' => 'site-header'])
            <a href="{{ route('order.create') }}"
                class="shine inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-brand-900/30 transition hover:-translate-y-0.5 hover:bg-brand-400">
                <x-icon name="cart" class="h-5 w-5" /> Commander
            </a>
            </div>
        </div>
    </header>

    {{-- ============ Héro ============ --}}
    <section class="relative isolate flex min-h-[92vh] items-center overflow-hidden bg-gradient-to-br from-cocoa-950 via-cocoa-900 to-brand-950 pb-20 pt-28 text-white">
        {{-- Halos chauds --}}
        <div class="drift pointer-events-none absolute -left-32 top-10 -z-10 h-[28rem] w-[28rem] rounded-full bg-brand-600/30 blur-3xl"></div>
        <div class="drift-slow pointer-events-none absolute -right-24 bottom-0 -z-10 h-[32rem] w-[32rem] rounded-full bg-brand-400/20 blur-3xl"></div>
        {{-- Grain lumineux discret --}}
        <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_70%_30%,rgba(253,188,109,0.12),transparent_55%)]"></div>

        {{-- Braises qui montent --}}
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
            @for ($i = 0; $i < 22; $i++)
                <span class="ember" style="
                    left: {{ ($i * 37 + 11) % 100 }}%;
                    --size: {{ 3 + ($i * 5) % 6 }}px;
                    --delay: -{{ round(fmod($i * 0.83, 8), 2) }}s;
                    --dur: {{ 6 + ($i * 1.3) % 6 }}s;
                    --drift: {{ (($i * 23) % 80) - 40 }}px;"></span>
            @endfor
        </div>

        <div class="mx-auto grid w-full max-w-6xl items-center gap-12 px-4 lg:grid-cols-2">
            <div>
                {{-- Statut en direct : ouvert de 19 h à 2 h --}}
                <div x-data="{ get open() { const h = new Date().getHours(); return h >= 19 || h < 2; } }"
                    class="inline-flex animate-fade-up items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-sm font-medium ring-1 ring-inset ring-white/15 backdrop-blur">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75" :class="open ? 'bg-emerald-400' : 'bg-brand-300'"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full" :class="open ? 'bg-emerald-400' : 'bg-brand-300'"></span>
                    </span>
                    <span x-text="open ? 'Ouvert maintenant · jusqu\'à 2 h' : 'Fermé · ouvre ce soir à 19 h'">Tous les soirs · 19 h – 2 h</span>
                </div>

                <h1 class="mt-6 text-5xl font-extrabold leading-[1.05] tracking-tight sm:text-6xl lg:text-7xl">
                    @foreach ($words as $index => $word)
                        {{-- Deux éléments : le flou de l'animation sur l'un, le dégradé sur l'autre.
                             Réunis sur le même, Chrome rend le mot invisible. --}}
                        <span class="word" style="--delay: {{ 0.15 + $index * 0.14 }}s"><span @class(['text-flame' => $loop->last])>{{ $word }}</span></span>
                    @endforeach
                </h1>

                <p class="mt-6 max-w-xl animate-fade-up text-lg leading-relaxed text-cocoa-200 [animation-delay:750ms]">
                    Grillades, pizzas, tacos, kebabs et douceurs maison — préparés avec soin,
                    chaque soir, rien que pour vous. Commandez en quelques clics, récupérez, régalez-vous.
                </p>

                <div class="mt-9 flex animate-fade-up flex-wrap gap-3 [animation-delay:900ms]">
                    <a href="{{ route('order.create') }}"
                        class="shine group inline-flex items-center gap-2 rounded-2xl bg-brand-500 px-7 py-4 text-base font-bold text-white shadow-xl shadow-brand-900/40 transition hover:-translate-y-1 hover:bg-brand-400">
                        Commander en ligne
                        <x-icon name="arrow-right" class="h-5 w-5 transition-transform duration-300 group-hover:translate-x-1.5" />
                    </a>
                    <a href="#menu"
                        class="inline-flex items-center gap-2 rounded-2xl px-7 py-4 text-base font-semibold text-white ring-1 ring-inset ring-white/25 transition hover:-translate-y-1 hover:bg-white/10">
                        Découvrir le menu
                    </a>
                </div>

                <div class="mt-8 flex flex-wrap items-center gap-2">
                    @foreach ($payments as $index => $method)
                        <span class="animate-fade-up rounded-full bg-white/5 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-brand-200 ring-1 ring-inset ring-white/10"
                            style="animation-delay: {{ 1050 + $index * 90 }}ms">{{ $method }}</span>
                    @endforeach
                </div>

                <div class="mt-10 flex animate-fade-up items-center gap-8 [animation-delay:1400ms]">
                    <div>
                        <div class="text-3xl font-extrabold text-white" x-data="countUp({{ $dishCount }})" x-text="display">{{ $dishCount }}</div>
                        <div class="text-xs uppercase tracking-wider text-cocoa-300">plats à la carte</div>
                    </div>
                    <div class="h-10 w-px bg-white/15"></div>
                    <div>
                        <div class="text-3xl font-extrabold text-white">19 h – 2 h</div>
                        <div class="text-xs uppercase tracking-wider text-cocoa-300">tous les soirs</div>
                    </div>
                </div>
            </div>

            {{-- Plats qui flottent (ordinateur) --}}
            <div class="relative hidden h-[30rem] lg:block" aria-hidden="true">
                @php
                    $spots = [
                        ['top-0 right-8 w-64', '-4deg', '0s'],
                        ['top-40 left-0 w-56', '5deg', '-2s'],
                        ['bottom-0 right-0 w-60', '-2deg', '-4s'],
                    ];
                @endphp
                @forelse ($gallery as $i => $product)
                    <div class="float absolute {{ $spots[$i][0] }}" style="--rot: {{ $spots[$i][1] }}; --delay: {{ $spots[$i][2] }}">
                        <div class="animate-fade-up overflow-hidden rounded-3xl bg-white/5 p-2 shadow-2xl shadow-black/40 ring-1 ring-white/15 backdrop-blur" style="animation-delay: {{ 500 + $i * 180 }}ms">
                            <img src="{{ $product->imageUrl() }}" alt="" class="h-40 w-full rounded-2xl object-cover" loading="eager" />
                            <div class="flex items-center justify-between gap-2 px-2 py-2.5">
                                <span class="truncate text-sm font-semibold text-white">{{ $product->name }}</span>
                                <span class="shrink-0 rounded-full bg-brand-500 px-2.5 py-0.5 text-xs font-bold text-white">@mru($product->sale_price)</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="float absolute inset-0 m-auto flex h-64 w-64 items-center justify-center rounded-full bg-gradient-to-br from-brand-400 to-brand-700 shadow-2xl">
                        <x-icon name="fire" class="h-32 w-32 text-white/90" />
                    </div>
                @endforelse
            </div>
        </div>

        <a href="#menu" class="nudge absolute bottom-6 left-1/2 -translate-x-1/2 rounded-full p-2 text-cocoa-200 hover:text-white" aria-label="Voir la suite">
            <x-icon name="chevron-down" class="h-7 w-7" />
        </a>
    </section>

    {{-- ============ Bandeau des spécialités qui défile ============ --}}
    <div class="marquee overflow-hidden bg-gradient-to-r from-brand-600 via-brand-500 to-brand-600 py-4 text-white" aria-hidden="true">
        <div class="marquee-track">
            @for ($copy = 0; $copy < 2; $copy++)
                @foreach ($specialties as $name)
                    <span class="flex items-center gap-6 px-6 text-lg font-extrabold uppercase tracking-[0.2em]">
                        {{ $name }}
                        <x-icon name="sparkles" class="h-5 w-5 text-brand-200" />
                    </span>
                @endforeach
            @endfor
        </div>
    </div>

    {{-- ============ Spécialités ============ --}}
    <section id="menu" class="scroll-mt-20 py-20 sm:py-24">
        <div class="mx-auto max-w-6xl px-4">
            <div class="reveal mx-auto max-w-2xl text-center">
                <span class="inline-flex items-center gap-2 rounded-full bg-brand-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-brand-700">
                    <x-icon name="fire" class="h-4 w-4" /> Tout juste sorti du grill
                </span>
                <h2 class="mt-4 text-4xl font-extrabold tracking-tight text-cocoa-900 sm:text-5xl">Nos spécialités</h2>
                <p class="mt-3 text-lg text-cocoa-600">Un aperçu de ce qui vous attend ce soir.</p>
            </div>

            <div class="mt-12 grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-3">
                @foreach ($featured as $product)
                    <a href="{{ route('order.create') }}" class="reveal group relative block overflow-hidden rounded-3xl bg-white shadow-soft ring-1 ring-cocoa-100 transition-all duration-500 hover:-translate-y-2 hover:shadow-lift"
                        style="--d: {{ ($loop->index % 3) * 110 }}ms">
                        <div class="relative overflow-hidden">
                            <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy"
                                class="h-40 w-full object-cover transition-transform duration-700 ease-out group-hover:scale-110 sm:h-56" />
                            <div class="absolute inset-0 bg-gradient-to-t from-cocoa-950/70 via-cocoa-950/0 to-transparent opacity-60 transition-opacity duration-500 group-hover:opacity-90"></div>
                            <span class="absolute right-3 top-3 rounded-full bg-white/95 px-3 py-1 text-sm font-extrabold text-brand-700 shadow-lg transition-transform duration-300 group-hover:scale-110">
                                @mru($product->sale_price)
                            </span>
                            <span class="absolute inset-x-3 bottom-3 flex translate-y-4 items-center justify-center gap-2 rounded-xl bg-brand-500 py-2 text-sm font-bold text-white opacity-0 shadow-lg transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100">
                                <x-icon name="plus" class="h-4 w-4" /> Commander
                            </span>
                        </div>
                        <div class="p-4 sm:p-5">
                            <div class="text-xs font-semibold uppercase tracking-wider text-brand-600">{{ $product->category->name ?? 'Spécialité' }}</div>
                            <div class="mt-1 text-base font-bold text-cocoa-900 sm:text-lg">{{ $product->name }}</div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="reveal mt-12 text-center">
                <a href="{{ route('order.create') }}"
                    class="group inline-flex items-center gap-2 rounded-2xl bg-cocoa-900 px-8 py-4 font-bold text-white shadow-lift transition hover:-translate-y-1 hover:bg-cocoa-800">
                    Voir tout le menu & commander
                    <x-icon name="arrow-right" class="h-5 w-5 transition-transform duration-300 group-hover:translate-x-1.5" />
                </a>
            </div>
        </div>
    </section>

    {{-- ============ Comment commander ============ --}}
    <section id="etapes" class="scroll-mt-20 bg-white py-20 sm:py-24">
        <div class="mx-auto max-w-6xl px-4">
            <div class="reveal mx-auto max-w-2xl text-center">
                <h2 class="text-4xl font-extrabold tracking-tight text-cocoa-900 sm:text-5xl">Simple comme 1, 2, 3</h2>
                <p class="mt-3 text-lg text-cocoa-600">Votre repas vous attend, sans file d'attente.</p>
            </div>

            <div class="relative mt-14 grid gap-10 md:grid-cols-3 md:gap-6">
                {{-- Ligne qui relie les étapes --}}
                <div class="pointer-events-none absolute left-[16%] right-[16%] top-10 hidden border-t-2 border-dashed border-brand-200 md:block"></div>

                @foreach ([
                    ['tag', 'Choisissez vos plats', 'Parcourez la carte et remplissez votre panier en quelques secondes.'],
                    ['orders', 'Commandez en ligne', 'Votre nom et votre numéro suffisent : la commande part directement en cuisine.'],
                    ['cash', 'Récupérez, régalez-vous', 'Vous payez au retrait : espèces, Bankily, Masrivi ou Sedad.'],
                ] as $i => [$icon, $title, $text])
                    <div class="reveal group relative text-center" style="--d: {{ $i * 150 }}ms">
                        <div class="relative mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-gradient-to-br from-brand-400 to-brand-600 text-white shadow-xl shadow-brand-500/30 transition-transform duration-500 group-hover:-translate-y-1 group-hover:rotate-6">
                            <x-icon :name="$icon" class="h-9 w-9" />
                            <span class="absolute -right-2 -top-2 flex h-8 w-8 items-center justify-center rounded-full bg-cocoa-900 text-sm font-extrabold text-white ring-4 ring-white">{{ $i + 1 }}</span>
                        </div>
                        <h3 class="mt-6 text-xl font-bold text-cocoa-900">{{ $title }}</h3>
                        <p class="mx-auto mt-2 max-w-xs text-cocoa-600">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ Infos pratiques ============ --}}
    <section id="infos" class="scroll-mt-20 py-20 sm:py-24">
        <div class="mx-auto grid max-w-6xl grid-cols-1 gap-5 px-4 sm:grid-cols-3">
            <div class="reveal group rounded-3xl bg-white p-8 text-center shadow-soft ring-1 ring-cocoa-100 transition duration-500 hover:-translate-y-1 hover:shadow-lift">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 transition-transform duration-500 group-hover:rotate-[20deg]">
                    <x-icon name="clock" class="h-8 w-8" />
                </span>
                <div class="mt-4 text-lg font-bold text-cocoa-900">Horaires</div>
                <div class="mt-1 text-cocoa-600">{{ config('restaurant.opening_hours') }}</div>
            </div>

            <div class="reveal group rounded-3xl bg-white p-8 text-center shadow-soft ring-1 ring-cocoa-100 transition duration-500 hover:-translate-y-1 hover:shadow-lift" style="--d: 120ms">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 transition-transform duration-500 group-hover:scale-110">
                    <x-icon name="cash" class="h-8 w-8" />
                </span>
                <div class="mt-4 text-lg font-bold text-cocoa-900">Paiement au retrait</div>
                <div class="mt-1 text-cocoa-600">Espèces · Bankily · Masrivi · Sedad</div>
            </div>

            {{-- WhatsApp : toute la carte est cliquable. --}}
            <a href="{{ $wa }}?text={{ rawurlencode('Bonjour Chez Traoré, ') }}" target="_blank" rel="noopener"
                class="reveal group block rounded-3xl bg-gradient-to-br from-emerald-500 to-emerald-700 p-8 text-center text-white shadow-soft transition duration-500 hover:-translate-y-1 hover:shadow-lift" style="--d: 240ms">
                <span class="relative mx-auto flex h-14 w-14 items-center justify-center">
                    <span class="absolute inset-0 animate-ping rounded-2xl bg-white/25"></span>
                    <span class="relative flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 transition-transform duration-500 group-hover:scale-110">
                        <i class="fa-brands fa-whatsapp" style="font-size:34px;color:#ffffff;" aria-hidden="true"></i>
                    </span>
                </span>
                <div class="mt-4 text-lg font-bold">WhatsApp</div>
                <div class="mt-1 text-emerald-50">{{ config('restaurant.phone_display') }}</div>
                <div class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-white/90">
                    Écrivez-nous <x-icon name="arrow-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                </div>
            </a>
        </div>
    </section>

    {{-- ============ Application à installer ============ --}}
    <section id="application" class="px-4 pb-16 sm:pb-20">
        <div class="reveal relative mx-auto grid max-w-6xl items-center gap-10 overflow-hidden rounded-[2rem] bg-cocoa-950 px-6 py-12 text-white shadow-lift sm:px-12 lg:grid-cols-2">
            <div class="drift pointer-events-none absolute -right-16 -top-24 h-72 w-72 rounded-full bg-brand-500/25 blur-3xl"></div>
            <div class="drift-slow pointer-events-none absolute -bottom-24 left-10 h-64 w-64 rounded-full bg-brand-700/30 blur-3xl"></div>

            <div class="relative">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-brand-200 ring-1 ring-white/15">
                    <x-icon name="phone" class="h-4 w-4" /> Application
                </span>
                <h2 class="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl">
                    Chez Traoré <span class="text-flame">dans votre poche</span>
                </h2>
                <p class="mt-3 max-w-md text-lg text-cocoa-300">
                    Installez l'application sur votre téléphone ou votre ordinateur : une icône, un geste, et le menu est là.
                </p>

                <ul class="mt-6 grid gap-3 text-sm text-cocoa-200 sm:grid-cols-3">
                    @foreach ([['bolt', 'Ouverture rapide'], ['phone', 'Plein écran'], ['download', 'Léger, sans store']] as $i => [$icon, $label])
                        <li class="reveal flex items-center gap-2 rounded-xl bg-white/5 px-3 py-2.5 ring-1 ring-white/10" style="--d: {{ 100 + $i * 90 }}ms">
                            <x-icon :name="$icon" class="h-5 w-5 shrink-0 text-brand-300" /> {{ $label }}
                        </li>
                    @endforeach
                </ul>

                <div class="mt-8">
                    @include('partials.install-app', ['variant' => 'site-card'])
                </div>
            </div>

            {{-- Téléphone stylisé avec l'icône de l'appli --}}
            <div class="relative mx-auto hidden h-[25rem] w-56 sm:block" aria-hidden="true">
                <div class="float absolute inset-0 rounded-[2.5rem] border-[10px] border-cocoa-800 bg-gradient-to-b from-cocoa-900 to-cocoa-950 shadow-2xl shadow-black/50" style="--rot: -3deg">
                    <div class="mx-auto mt-2 h-4 w-20 rounded-full bg-cocoa-800"></div>
                    <div class="mt-8 grid grid-cols-3 gap-4 px-5">
                        @for ($i = 0; $i < 6; $i++)
                            <span class="aspect-square rounded-xl bg-white/[0.06]"></span>
                        @endfor
                        <span class="col-start-2 flex flex-col items-center gap-1.5">
                            <img src="/icons/icon-192.png" alt="" class="animate-soft-pulse aspect-square w-full rounded-xl shadow-lg shadow-brand-900/60 ring-2 ring-brand-300/50">
                            <span class="text-[9px] font-semibold text-white">Chez Traoré</span>
                        </span>
                    </div>
                    <div class="absolute inset-x-5 bottom-6 flex justify-between rounded-2xl bg-white/[0.06] p-3">
                        @for ($i = 0; $i < 4; $i++)
                            <span class="h-7 w-7 rounded-lg bg-white/10"></span>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ Dernier appel ============ --}}
    <section class="px-4 pb-20 sm:pb-24">
        <div class="reveal relative mx-auto max-w-6xl overflow-hidden rounded-[2rem] bg-gradient-to-br from-brand-500 via-brand-600 to-brand-800 px-6 py-14 text-center text-white shadow-lift sm:px-12">
            <div class="drift pointer-events-none absolute -left-10 -top-16 h-64 w-64 rounded-full bg-brand-300/40 blur-3xl"></div>
            <div class="drift-slow pointer-events-none absolute -bottom-20 -right-10 h-72 w-72 rounded-full bg-cocoa-900/40 blur-3xl"></div>
            <div class="relative">
                <h2 class="text-3xl font-extrabold tracking-tight sm:text-5xl">Une petite faim ce soir ?</h2>
                <p class="mx-auto mt-3 max-w-xl text-lg text-brand-50">Commandez maintenant, on s'occupe du reste.</p>
                <a href="{{ route('order.create') }}"
                    class="shine group mt-8 inline-flex items-center gap-2 rounded-2xl bg-white px-8 py-4 font-extrabold text-brand-700 shadow-xl transition hover:-translate-y-1 hover:shadow-2xl">
                    <x-icon name="cart" class="h-5 w-5" /> Je commande
                    <x-icon name="arrow-right" class="h-5 w-5 transition-transform duration-300 group-hover:translate-x-1.5" />
                </a>
            </div>
        </div>
    </section>

    {{-- ============ Pied de page ============ --}}
    <footer class="bg-cocoa-950 text-cocoa-300">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-10 text-sm sm:flex-row">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 text-white">
                    <x-icon name="fire" class="h-5 w-5" />
                </span>
                <span>© {{ date('Y') }} Chez Traoré · Nouakchott</span>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-5">
                <a href="{{ $wa }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 transition hover:text-brand-300">
                    <i class="fa-brands fa-whatsapp" aria-hidden="true"></i> WhatsApp {{ config('restaurant.phone_display') }}
                </a>
                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="text-cocoa-400 transition hover:text-brand-300">Espace équipe</a>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="mx-auto max-w-6xl px-4 py-4 text-center text-xs text-cocoa-400">
                Développé par <span class="font-semibold text-brand-300">Bemouss</span>
            </div>
        </div>
    </footer>

    {{-- ============ Bouton WhatsApp flottant ============ --}}
    <a href="{{ $wa }}?text={{ rawurlencode('Bonjour Chez Traoré, ') }}" target="_blank" rel="noopener"
        class="fixed bottom-5 right-5 z-40 flex h-14 w-14 animate-pop items-center justify-center rounded-full bg-[#25D366] text-white shadow-xl shadow-emerald-900/30 transition hover:scale-110 [animation-delay:1800ms]"
        aria-label="Écrire à Chez Traoré sur WhatsApp">
        <span class="absolute inset-0 animate-ping rounded-full bg-[#25D366] opacity-40"></span>
        <i class="fa-brands fa-whatsapp relative" style="font-size:30px;" aria-hidden="true"></i>
    </a>

    {{-- Apparition des blocs au défilement --}}
    <script>
        (function () {
            var els = document.querySelectorAll('.reveal');
            if (!('IntersectionObserver' in window)) {
                els.forEach(function (el) { el.classList.add('is-visible'); });
                return;
            }
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
            els.forEach(function (el) { io.observe(el); });
        })();
    </script>
</body>
</html>
