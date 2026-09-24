<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#2e211c">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="app-shell bg-cream font-sans text-gray-800 antialiased">
        <div x-data="{ sidebarOpen: false }" x-on:keydown.escape.window="sidebarOpen = false" class="min-h-screen">
            @include('layouts.navigation')

            <div class="lg:pl-64">
                {{-- Barre du haut --}}
                <header class="sticky top-0 z-20 border-b border-cocoa-100/80 bg-cream/85 backdrop-blur-md">
                    <div class="flex min-h-[4rem] items-center gap-3 px-4 py-3 sm:px-6 lg:px-8">
                        <button type="button" x-on:click="sidebarOpen = true"
                            class="-ms-1 rounded-lg p-2 text-cocoa-700 transition hover:bg-white hover:shadow-soft lg:hidden"
                            aria-label="Ouvrir le menu">
                            <x-icon name="menu" class="h-6 w-6" />
                        </button>

                        <div class="min-w-0 flex-1">
                            @isset($header)
                                {{ $header }}
                            @endisset
                        </div>

                        <div class="hidden items-center gap-2 sm:flex">
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-cocoa-100 bg-white px-3 py-1.5 text-xs font-medium text-cocoa-700 shadow-sm"
                                title="Service de 19 h à 2 h">
                                <x-icon name="clock" class="h-4 w-4 text-brand-600" />
                                <span x-data="clock" x-text="time">{{ now()->format('H:i') }}</span>
                            </span>
                        </div>
                    </div>
                </header>

                <main class="pb-12">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
