@php
    $tab = fn (bool $active) => $active
        ? 'px-4 py-2 rounded-md text-sm font-semibold bg-indigo-600 text-white'
        : 'px-4 py-2 rounded-md text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200';
@endphp

<div class="bg-white shadow sm:rounded-lg p-3 flex flex-wrap gap-2">
    <a href="{{ route('reports.daily') }}" class="{{ $tab(request()->routeIs('reports.daily')) }}">Journalier</a>
    <a href="{{ route('reports.weekly') }}" class="{{ $tab(request()->routeIs('reports.weekly')) }}">Hebdomadaire</a>
    <a href="{{ route('reports.monthly') }}" class="{{ $tab(request()->routeIs('reports.monthly')) }}">Mensuel</a>
    <a href="{{ route('reports.stock') }}" class="{{ $tab(request()->routeIs('reports.stock')) }}">Contrôle stock</a>
    <a href="{{ route('reconciliation.index') }}" class="{{ $tab(request()->routeIs('reconciliation.*')) }}">Réconciliation</a>
</div>
