<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Fournisseurs</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">{{ $errors->first() }}</div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6" x-data="{ editing: null }">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <h3 class="font-medium text-gray-800">Comptes fournisseurs</h3>
                    <div class="text-sm">Total dû aujourd'hui : <span class="font-bold text-red-700">@mru($totalDue)</span></div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-3 py-2">Fournisseur</th>
                                <th class="px-3 py-2">Articles rattachés</th>
                                <th class="px-3 py-2">Téléphone</th>
                                <th class="px-3 py-2 text-right">Solde</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($suppliers as $supplier)
                                @php($balance = $supplier->balance())
                                <tr>
                                    <td class="px-3 py-2 font-medium text-gray-900">
                                        {{ $supplier->name }}
                                        @unless ($supplier->is_active)
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-600">inactif</span>
                                        @endunless
                                    </td>
                                    <td class="px-3 py-2 text-gray-600">
                                        {{ $supplier->stockItems->pluck('name')->implode(', ') ?: '—' }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-600">{{ $supplier->phone ?: '—' }}</td>
                                    <td class="px-3 py-2 text-right font-semibold {{ $balance > 0 ? 'text-red-700' : 'text-green-700' }}">
                                        @mru(abs($balance))
                                        @if ($balance < 0)
                                            <span class="text-xs font-normal text-gray-500">d'avance</span>
                                        @elseif ($balance > 0)
                                            <span class="text-xs font-normal text-gray-500">dû</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('suppliers.show', $supplier) }}" class="text-indigo-600 hover:underline">Relevé</a>
                                            <button type="button" x-on:click="editing = (editing === {{ $supplier->id }} ? null : {{ $supplier->id }})"
                                                class="text-gray-600 hover:underline">Modifier</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr x-show="editing === {{ $supplier->id }}" style="display: none;">
                                    <td colspan="5" class="px-3 py-3 bg-gray-50">
                                        <form method="POST" action="{{ route('suppliers.update', $supplier) }}"
                                            class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <x-input-label value="Nom" />
                                                <x-text-input name="name" type="text" class="mt-1 block w-full" :value="$supplier->name" required />
                                            </div>
                                            <div>
                                                <x-input-label value="Téléphone" />
                                                <x-text-input name="phone" type="text" class="mt-1 block w-full" :value="$supplier->phone" />
                                            </div>
                                            <div>
                                                <x-input-label value="Note" />
                                                <x-text-input name="note" type="text" class="mt-1 block w-full" :value="$supplier->note" />
                                            </div>
                                            <div class="flex flex-wrap items-center gap-3">
                                                <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                                                    <input type="checkbox" name="is_active" value="1" @checked($supplier->is_active)
                                                        class="rounded border-gray-300 text-indigo-600" />
                                                    Actif
                                                </label>
                                                <x-primary-button>Enregistrer</x-primary-button>
                                            </div>
                                        </form>
                                        <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="mt-3"
                                            onsubmit="return confirm('Supprimer définitivement {{ $supplier->name }} ? Impossible s\'il a déjà des livraisons.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-sm text-red-600 hover:underline">Supprimer ce fournisseur</button>
                                            <span class="text-xs text-gray-500">
                                                — possible seulement s'il n'a aucune livraison ni règlement. Sinon, décoche « Actif » :
                                                il disparaît des listes de saisie et son historique est conservé.
                                            </span>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-3 py-6 text-center text-gray-500">Aucun fournisseur. Ajoute le boulanger et le boucher ci-dessous.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-1">Ajouter un fournisseur</h3>
                <p class="text-sm text-gray-500 mb-4">Pour les fournisseurs à abonnement : on prend la marchandise pendant le mois, on paie à la fin.</p>
                <form method="POST" action="{{ route('suppliers.store') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                    @csrf
                    <div>
                        <x-input-label for="name" value="Nom" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" placeholder="Ex : Boulangerie Sidi" required />
                    </div>
                    <div>
                        <x-input-label for="phone" value="Téléphone" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone')" />
                    </div>
                    <div>
                        <x-input-label for="note" value="Note (facultatif)" />
                        <x-text-input id="note" name="note" type="text" class="mt-1 block w-full" :value="old('note')" placeholder="Ex : pain arabe, livré le matin" />
                    </div>
                    <div>
                        <x-primary-button>Ajouter</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
