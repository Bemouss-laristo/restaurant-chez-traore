<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nouvel article de stock</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg">
                <form method="POST" action="{{ route('stock-items.store') }}">
                    @csrf
                    @include('stock._form', ['item' => null])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
