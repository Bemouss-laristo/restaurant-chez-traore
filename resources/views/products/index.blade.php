<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Produits</h2>
            <a href="{{ route('products.create') }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                + Nouveau produit
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-4">
                <form method="GET" action="{{ route('products.index') }}" class="flex flex-wrap gap-2 items-center">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Rechercher un produit…"
                        class="border-gray-300 rounded-md shadow-sm w-full max-w-xs" />
                    <select name="category_id" class="border-gray-300 rounded-md shadow-sm">
                        <option value="">Toutes les catégories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($categoryId === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Filtrer</button>
                </form>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @forelse ($products as $product)
                    <a href="{{ route('products.edit', $product) }}"
                        class="bg-white shadow sm:rounded-lg overflow-hidden hover:shadow-md transition group">
                        <div class="relative">
                            <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}"
                                class="h-36 w-full object-cover" />
                            @unless ($product->is_active)
                                <span class="absolute top-2 left-2 px-2 py-1 rounded-full text-xs font-medium bg-gray-800/80 text-white">Inactif</span>
                            @endunless
                        </div>
                        <div class="p-3">
                            <div class="text-xs text-gray-500">{{ $product->category->name ?? '—' }}</div>
                            <div class="font-medium text-gray-900 group-hover:text-indigo-600">{{ $product->name }}</div>
                            <div class="mt-1 font-semibold text-gray-800">@mru($product->sale_price)</div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full bg-white shadow sm:rounded-lg p-8 text-center text-gray-500">
                        Aucun produit trouvé.
                    </div>
                @endforelse
            </div>

            <div>{{ $products->links() }}</div>
        </div>
    </div>
</x-app-layout>
