<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dépenses</h2>
            <a href="{{ route('expenses.create') }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                + Nouvelle dépense
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <form method="GET" action="{{ route('expenses.index') }}" class="flex gap-2 items-center">
                        <select name="category" class="border-gray-300 rounded-md shadow-sm">
                            <option value="">Toutes les catégories</option>
                            @foreach ($categories as $value => $label)
                                <option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Filtrer</button>
                    </form>
                    <div class="text-sm text-gray-600">
                        Total affiché : <span class="font-semibold text-gray-900">@mru($total)</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-4 py-2">Date</th>
                                <th class="px-4 py-2">Catégorie</th>
                                <th class="px-4 py-2">Description</th>
                                <th class="px-4 py-2">Paiement</th>
                                <th class="px-4 py-2 text-right">Montant</th>
                                <th class="px-4 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($expenses as $expense)
                                <tr>
                                    <td class="px-4 py-3 text-gray-600">{{ $expense->spent_at->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3">{{ $expense->expense_category->label() }}</td>
                                    <td class="px-4 py-3 text-gray-600">{!! $expense->description ? nl2br(e($expense->description)) : '—' !!}</td>
                                    <td class="px-4 py-3">{{ $expense->payment_method->label() }}</td>
                                    <td class="px-4 py-3 text-right font-medium">@mru($expense->amount)</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('expenses.edit', $expense) }}" class="text-indigo-600 hover:underline">Modifier</a>
                                            <form method="POST" action="{{ route('expenses.destroy', $expense) }}"
                                                onsubmit="return confirm('Supprimer cette dépense ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-red-600 hover:underline">Supprimer</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Aucune dépense enregistrée.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $expenses->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
