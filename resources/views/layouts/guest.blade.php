<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Chez Traoré') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 bg-stone-900">
            <a href="/" class="text-center">
                <div class="text-3xl font-semibold text-white">Chez Traoré</div>
                <div class="text-amber-400 text-sm mt-1 tracking-widest">GESTION DU RESTAURANT</div>
            </a>

            <div class="w-full sm:max-w-md mt-8 px-6 py-8 bg-white shadow-xl overflow-hidden rounded-lg border-t-4 border-amber-500">
                {{ $slot }}
            </div>

            <div class="mt-6 text-stone-500 text-xs">© {{ date('Y') }} Chez Traoré</div>
        </div>
    </body>
</html>
