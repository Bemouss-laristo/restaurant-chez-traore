{{--
    Bouton « Installer l'application » + fenêtre d'aide.

    @include('partials.install-app', ['variant' => 'sidebar' | 'login' | 'site-header' | 'site-card'])

    Invisible quand le site tourne déjà comme application installée.
    Chrome, Edge et Android : ouvre directement la fenêtre d'installation.
    iPhone, Firefox, etc. : affiche la marche à suivre pour cet appareil.
--}}
@php($variant = $variant ?? 'sidebar')

<div x-data="installApp" x-show="! installed" @class([
    'contents' => $variant !== 'site-card',
])>
    @switch($variant)
        @case('sidebar')
            <button type="button" x-on:click="install()"
                class="group mb-2 flex w-full items-center gap-3 rounded-xl border border-brand-400/25 bg-brand-500/10 px-3 py-2.5 text-left text-sm font-medium text-brand-100 transition hover:border-brand-400/50 hover:bg-brand-500/20 hover:text-white">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-500/25 text-brand-200 transition-transform duration-300 group-hover:-translate-y-0.5">
                    <x-icon name="download" class="h-4 w-4" />
                </span>
                <span class="leading-tight">
                    Installer l'application
                    <span class="block text-[11px] font-normal text-cocoa-300">Ordinateur ou téléphone</span>
                </span>
            </button>
            @break

        @case('login')
            <button type="button" x-on:click="install()"
                class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-xs font-medium text-cocoa-200 transition hover:border-brand-400/60 hover:bg-white/10 hover:text-white">
                <x-icon name="download" class="h-4 w-4 text-brand-300" /> Installer l'application sur cet appareil
            </button>
            @break

        @case('site-header')
            <button type="button" x-on:click="install()" title="Installer l'application" aria-label="Installer l'application"
                class="hidden items-center gap-2 rounded-xl border border-white/20 bg-white/5 px-3 py-2.5 text-sm font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:border-brand-300/70 hover:bg-white/10 sm:inline-flex">
                <x-icon name="download" class="h-5 w-5" />
                <span class="hidden lg:inline">Installer l'appli</span>
            </button>
            @break

        @case('site-card')
            <div class="flex flex-col gap-3 sm:flex-row">
                <button type="button" x-on:click="install()"
                    class="shine group inline-flex items-center justify-center gap-2 rounded-2xl bg-brand-500 px-7 py-4 font-extrabold text-white shadow-lg shadow-brand-900/40 transition hover:-translate-y-1 hover:bg-brand-400">
                    <x-icon name="download" class="h-5 w-5 transition-transform duration-300 group-hover:translate-y-0.5" />
                    Installer l'application
                </button>
                <span class="inline-flex items-center gap-2 text-sm text-cocoa-300" x-show="ready" x-cloak>
                    <x-icon name="check" class="h-4 w-4 text-green-400" /> Prête à installer sur cet appareil
                </span>
            </div>
            @break
    @endswitch

    {{-- Fenêtre d'aide : placée à la racine de la page pour s'afficher au-dessus de tout. --}}
    <template x-teleport="body">
        <div x-show="help" x-cloak x-on:keydown.escape.window="help = false"
            class="fixed inset-0 z-[100] flex items-end justify-center p-4 sm:items-center"
            role="dialog" aria-modal="true" aria-labelledby="install-help-title-{{ $variant }}">
            <div x-show="help" x-transition.opacity class="absolute inset-0 bg-cocoa-950/70 backdrop-blur-sm" x-on:click="help = false"></div>

            <div x-show="help"
                x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-6 opacity-0 sm:scale-95" x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="relative w-full max-w-md overflow-hidden rounded-3xl bg-white text-left text-cocoa-800 shadow-2xl">
                <div class="relative bg-gradient-to-br from-brand-400 to-brand-700 px-6 pb-6 pt-7 text-white">
                    <button type="button" x-on:click="help = false" class="absolute right-3 top-3 rounded-lg p-1.5 text-white/80 transition hover:bg-white/15 hover:text-white" aria-label="Fermer">
                        <x-icon name="close" class="h-5 w-5" />
                    </button>
                    <img src="/icons/icon-192.png" alt="" class="h-14 w-14 rounded-2xl shadow-lg ring-2 ring-white/40">
                    <h2 id="install-help-title-{{ $variant }}" class="mt-4 text-xl font-extrabold">Installer Chez Traoré</h2>
                    <p class="mt-1 text-sm text-brand-50">Une icône sur votre écran, l'application s'ouvre en plein écran.</p>
                </div>

                <div class="space-y-4 px-6 py-6 text-sm">
                    {{-- iPhone / iPad --}}
                    <template x-if="platform === 'ios'">
                        <ol class="space-y-3">
                            <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">1</span><span>Ouvrez ce site dans <strong>Safari</strong>.</span></li>
                            <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">2</span><span class="flex flex-wrap items-center gap-1">Touchez le bouton <strong>Partager</strong> <x-icon name="share" class="inline h-5 w-5 text-brand-600" /> en bas de l'écran.</span></li>
                            <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">3</span><span>Choisissez <strong>« Sur l'écran d'accueil »</strong>, puis <strong>Ajouter</strong>.</span></li>
                        </ol>
                    </template>

                    {{-- Android (Chrome, Samsung, Firefox) --}}
                    <template x-if="['android', 'samsung', 'firefox-android'].includes(platform)">
                        <ol class="space-y-3">
                            <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">1</span>
                                <span>Ouvrez le <strong>menu</strong> du navigateur
                                    <span x-show="platform === 'samsung'">(☰ en bas de l'écran)</span><span x-show="platform !== 'samsung'">(⋮ en haut à droite)</span>.</span></li>
                            <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">2</span><span>Touchez <strong>« Installer l'application »</strong> ou <strong>« Ajouter à l'écran d'accueil »</strong>.</span></li>
                            <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">3</span><span>Confirmez : l'icône Chez Traoré apparaît avec vos applications.</span></li>
                        </ol>
                    </template>

                    {{-- Chrome / Edge sur ordinateur, quand la fenêtre ne s'est pas ouverte d'elle-même --}}
                    <template x-if="platform === 'desktop'">
                        <ol class="space-y-3">
                            <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">1</span><span>Cliquez sur l'icône d'installation <x-icon name="download" class="inline h-4 w-4 text-brand-600" /> à droite de la barre d'adresse,</span></li>
                            <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">2</span><span>ou ouvrez le menu <strong>⋮</strong> et choisissez <strong>« Installer Chez Traoré »</strong>.</span></li>
                            <li class="flex gap-3"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">3</span><span>Si le choix n'apparaît pas, l'application est sans doute déjà installée : cherchez « Chez Traoré » dans vos applications.</span></li>
                        </ol>
                    </template>

                    {{-- Firefox sur ordinateur : pas d'installation possible --}}
                    <template x-if="platform === 'firefox-desktop'">
                        <div class="space-y-3">
                            <p class="rounded-xl bg-amber-50 px-4 py-3 text-amber-900 ring-1 ring-amber-200">
                                Firefox sur ordinateur ne sait pas installer un site comme application.
                            </p>
                            <p>Pour avoir l'icône Chez Traoré sur l'ordinateur, ouvrez <strong>cheztraore.com</strong> une fois dans <strong>Google Chrome</strong> ou <strong>Microsoft Edge</strong>, puis cliquez sur « Installer l'application ».</p>
                            <p class="text-cocoa-500">Vous pouvez continuer à travailler dans Firefox : tout fonctionne pareil.</p>
                        </div>
                    </template>

                    <button type="button" x-on:click="help = false"
                        class="w-full rounded-xl bg-cocoa-900 px-4 py-3 font-semibold text-white transition hover:bg-cocoa-800">
                        J'ai compris
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
