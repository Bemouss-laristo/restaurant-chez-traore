<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dépenses du jour</h2>
            <a href="{{ route('caisse.index') }}" class="text-sm text-indigo-600 hover:underline">Voir la caisse</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">{{ session('error') }}</div>
            @endif

            @if ($session === null)
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-md">
                    ⚠ Aucune caisse ouverte. Ouvre la caisse pour pouvoir noter une dépense.
                    <a href="{{ route('caisse.index') }}" class="underline font-medium">Ouvrir la caisse</a>
                </div>
            @endif

            {{-- Formulaire --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-1">Noter une dépense</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Pour l'argent pris dans la caisse (espèces). Le montant est retiré de la caisse théorique.
                </p>

                <form method="POST" action="{{ route('cashier-expenses.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                    @csrf
                    <div>
                        <x-input-label for="amount" value="Montant (MRU)" />
                        <x-text-input id="amount" name="amount" type="number" step="1" min="1" inputmode="numeric"
                            class="mt-1 block w-full" :value="old('amount')" required autofocus />
                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="expense_category" value="Catégorie" />
                        <select id="expense_category" name="expense_category"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach ($categories as $value => $label)
                                <option value="{{ $value }}" @selected(old('expense_category', 'achat_marchandises') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('expense_category')" class="mt-2" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="description" value="À quoi a servi l'argent ?" />
                        <x-text-input id="description" name="description" type="text" class="mt-1 block w-full"
                            :value="old('description')" placeholder="Ex : pain, sachets, taxi livraison…" required maxlength="255" />
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" @disabled($session === null)
                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            Enregistrer la dépense
                        </button>
                    </div>
                </form>
            </div>

            {{-- Liste du jour --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <h3 class="font-medium text-gray-800">Mes dépenses d'aujourd'hui</h3>
                    <div class="text-sm">
                        Total : <span class="font-semibold text-red-700">@mru($total)</span>
                        @if ($expected !== null)
                            <span class="ms-3 text-gray-500">Caisse théorique : <span class="font-semibold text-indigo-700">@mru($expected)</span></span>
                        @endif
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-3 py-2">Heure</th>
                                <th class="px-3 py-2">Catégorie</th>
                                <th class="px-3 py-2">Description</th>
                                <th class="px-3 py-2 text-right">Montant</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($expenses as $expense)
                                <tr>
                                    <td class="px-3 py-2 text-gray-600">{{ $expense->created_at->format('H:i') }}</td>
                                    <td class="px-3 py-2">{{ $expense->expense_category->label() }}</td>
                                    <td class="px-3 py-2 text-gray-700">{{ $expense->description }}</td>
                                    <td class="px-3 py-2 text-right font-medium">@mru($expense->amount)</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-3 py-6 text-center text-gray-500">Aucune dépense notée aujourd'hui.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-gray-500">Une erreur ? Demande au gérant de la corriger.</p>
            </div>
        </div>
    </div>
</x-app-layout>
