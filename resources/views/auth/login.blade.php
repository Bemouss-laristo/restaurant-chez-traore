<x-guest-layout>
    <h2 class="mb-1 text-center text-xl font-bold text-cocoa-900">Bon retour</h2>
    <p class="mb-6 text-center text-sm text-cocoa-500">Connecte-toi pour ouvrir le service.</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <x-input-label for="email" value="Adresse email" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Mot de passe" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-cocoa-300 text-brand-600 shadow-sm focus:ring-brand-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">Se souvenir de moi</span>
            </label>
        </div>

        <div class="flex items-center justify-between mt-6">
            @if (Route::has('password.request'))
                <a class="text-sm text-cocoa-600 underline hover:text-brand-700" href="{{ route('password.request') }}">
                    Mot de passe oublié ?
                </a>
            @endif

            <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-6 py-2.5 font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:-translate-y-0.5 hover:bg-brand-700 active:translate-y-0">
                Se connecter <x-icon name="arrow-right" class="h-4 w-4" />
            </button>
        </div>
    </form>
</x-guest-layout>
