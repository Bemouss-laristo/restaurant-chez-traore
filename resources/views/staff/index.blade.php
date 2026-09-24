<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Employés et salaires — {{ $monthLabel }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">{{ $errors->first() }}</div>
            @endif

            <form method="GET" class="bg-white shadow sm:rounded-lg p-3 flex flex-wrap items-center gap-2">
                <label class="text-sm text-gray-600">Mois :</label>
                <input type="month" name="month" value="{{ $month }}" class="border-gray-300 rounded-md shadow-sm" />
                <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Afficher</button>
            </form>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Masse salariale du mois</div><div class="text-2xl font-bold text-gray-800 mt-1">@mru($payroll)</div></div>
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Déjà payé</div><div class="text-2xl font-bold text-green-700 mt-1">@mru($paidTotal)</div></div>
                <div class="bg-white shadow sm:rounded-lg p-5"><div class="text-sm text-gray-500">Reste à payer</div><div class="text-2xl font-bold text-red-700 mt-1">@mru($remainingTotal)</div></div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-4">Équipe</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-3 py-2">Employé</th>
                                <th class="px-3 py-2">Fonction</th>
                                <th class="px-3 py-2 text-right">Salaire</th>
                                <th class="px-3 py-2 text-right">Payé ce mois</th>
                                <th class="px-3 py-2">Statut</th>
                                <th class="px-3 py-2">Enregistrer un paiement</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($rows as $row)
                                @php($person = $row['person'])
                                <tr class="{{ $person->is_active ? '' : 'text-gray-400' }}">
                                    <td class="px-3 py-2 font-medium text-gray-900">
                                        {{ $person->name }}
                                        @unless ($person->is_active)
                                            <span class="ms-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">inactif</span>
                                        @endunless
                                        <div class="text-xs text-gray-400">{{ $person->phone ?: '' }}</div>
                                    </td>
                                    <td class="px-3 py-2 text-gray-700">{{ $person->job_title }}</td>
                                    <td class="px-3 py-2 text-right">@mru($person->monthly_salary)</td>
                                    <td class="px-3 py-2 text-right text-green-700">@mru($row['paid'])</td>
                                    <td class="px-3 py-2">
                                        @if ($row['remaining'] <= 0 && (float) $person->monthly_salary > 0)
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Payé</span>
                                        @elseif ($row['paid'] > 0)
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Partiel — reste @mru($row['remaining'])</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Non payé</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        @if ($person->is_active)
                                            <form method="POST" action="{{ route('staff.pay', $person) }}" class="flex flex-wrap items-center gap-2">
                                                @csrf
                                                <input type="hidden" name="month" value="{{ $month }}" />
                                                <input type="number" name="amount" step="1" min="1" value="{{ (int) max(0, $row['remaining']) ?: (int) $person->monthly_salary }}"
                                                    class="w-28 text-right border-gray-300 rounded-md shadow-sm" />
                                                <select name="payment_method" class="border-gray-300 rounded-md shadow-sm text-sm">
                                                    @foreach ($paymentMethods as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="px-3 py-2 bg-brand-600 text-white text-sm font-semibold rounded-md hover:bg-brand-700">Payer</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-6 text-center text-gray-500">Aucun employé enregistré. Ajoute ton équipe ci-dessous.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-gray-500">Chaque paiement est enregistré comme une dépense « Salaires », et sort de la caisse s'il est payé en espèces.</p>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-800 mb-4">Ajouter un employé</h3>
                <form method="POST" action="{{ route('staff.store') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    @csrf
                    <div>
                        <x-input-label for="name" value="Nom" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                    </div>
                    <div>
                        <x-input-label for="job_title" value="Fonction" />
                        <x-text-input id="job_title" name="job_title" type="text" class="mt-1 block w-full" :value="old('job_title')" placeholder="Ex : Cuisinier" required />
                    </div>
                    <div>
                        <x-input-label for="monthly_salary" value="Salaire mensuel (MRU)" />
                        <x-text-input id="monthly_salary" name="monthly_salary" type="number" step="1" min="0" class="mt-1 block w-full" :value="old('monthly_salary', 0)" required />
                    </div>
                    <div>
                        <x-input-label for="phone" value="Téléphone" />
                        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone')" />
                    </div>
                    <div>
                        <x-primary-button>Ajouter</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
