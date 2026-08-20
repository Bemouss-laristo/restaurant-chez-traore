@php($isEdit = isset($user) && $user)

<div class="space-y-6">
    <div>
        <x-input-label for="name" value="Nom complet" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
            :value="old('name', $user->name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email" value="Adresse email" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
            :value="old('email', $user->email ?? '')" required />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="role" value="Rôle" />
        <select id="role" name="role"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $user->role->value ?? 'caissier') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('role')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="password"
            :value="$isEdit ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe'" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
            :required="! $isEdit" autocomplete="new-password" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="password_confirmation" value="Confirmer le mot de passe" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password"
            class="mt-1 block w-full" autocomplete="new-password" />
    </div>

    <label class="flex items-center">
        <input type="checkbox" name="is_active" value="1"
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
            @checked(old('is_active', $user->is_active ?? true)) />
        <span class="ms-2 text-sm text-gray-600">Compte actif</span>
    </label>

    <div class="flex items-center gap-4">
        <x-primary-button>{{ $isEdit ? 'Mettre à jour' : "Créer l'employé" }}</x-primary-button>
        <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 underline">Annuler</a>
    </div>
</div>
