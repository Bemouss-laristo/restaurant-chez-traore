<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestion des employés</h2>
            <a href="{{ route('admin.users.create') }}"
                class="inline-flex items-center px-4 py-2 bg-brand-600 text-white text-sm font-semibold rounded-md hover:bg-brand-700">
                + Nouvel employé
            </a>
        </div>
    </x-slot>

    @php($rows = $users->map(fn ($u) => trim($u->name.' '.$u->email.' '.$u->role->label()))->values())

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6" x-data="liveSearch({
                rows: @js($rows),
                get shown() { return this.visibleCount; },
            })">
                <div class="mb-4 flex flex-wrap gap-2 items-center">
                    <input type="search" x-model="search" placeholder="Rechercher un nom ou un email…" autocomplete="off"
                        class="border-gray-300 rounded-md shadow-sm w-full max-w-sm" />
                    <span class="text-sm text-gray-500" x-show="search.trim() !== ''"
                        x-text="shown + ' employé(s) sur {{ $users->count() }}'"></span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-4 py-2">Nom</th>
                                <th class="px-4 py-2">Email</th>
                                <th class="px-4 py-2">Rôle</th>
                                <th class="px-4 py-2">Statut</th>
                                <th class="px-4 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($users as $user)
                                <tr x-show="match(@js($rows[$loop->index]))">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $user->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                                    <td class="px-4 py-3">
                                        @php($badge = match ($user->role->value) {
                                            'admin' => 'bg-purple-100 text-purple-800',
                                            'gerant' => 'bg-blue-100 text-blue-800',
                                            default => 'bg-emerald-100 text-emerald-800',
                                        })
                                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $badge }}">
                                            {{ $user->role->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($user->is_active)
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Actif</span>
                                        @else
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-200 text-gray-600">Inactif</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('admin.users.edit', $user) }}"
                                                class="text-brand-600 hover:underline">Modifier</a>

                                            <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="text-amber-600 hover:underline">
                                                    {{ $user->is_active ? 'Désactiver' : 'Activer' }}
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                                onsubmit="return confirm('Supprimer définitivement cet employé ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-red-600 hover:underline">Supprimer</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">Aucun employé enregistré.</td>
                                </tr>
                            @endforelse
                            @if ($users->isNotEmpty())
                                <tr x-show="shown === 0">
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                        Aucun employé ne correspond à cette recherche.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('partials.live-search')
</x-app-layout>
