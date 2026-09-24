<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#2e211c">

        <title>{{ config('app.name', 'Chez Traoré') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-gradient-to-br from-cocoa-900 via-cocoa-950 to-brand-950 px-4 py-10">
            {{-- Halos chauds, purement décoratifs --}}
            <div class="pointer-events-none absolute -left-24 -top-24 h-80 w-80 rounded-full bg-brand-500/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-32 -right-16 h-96 w-96 rounded-full bg-brand-400/10 blur-3xl"></div>

            <a href="/" class="relative flex animate-fade-up flex-col items-center text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 text-white shadow-lg shadow-brand-900/50">
                    <x-icon name="fire" class="h-8 w-8" />
                </span>
                <span class="mt-4 text-3xl font-bold text-white">Chez <span class="text-brand-300">Traoré</span></span>
                <span class="mt-1 text-xs uppercase tracking-[0.25em] text-cocoa-300">Gestion du restaurant</span>
            </a>

            <div class="relative mt-8 w-full animate-fade-up rounded-2xl bg-white px-6 py-8 shadow-2xl ring-1 ring-black/5 [animation-delay:100ms] sm:max-w-md sm:px-8">
                {{ $slot }}
            </div>

            <div class="relative mt-6 text-xs text-cocoa-400">© {{ date('Y') }} Chez Traoré</div>
        </div>
    </body>
</html>
