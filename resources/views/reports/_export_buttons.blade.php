@php($current = request()->route()->getName())
<div class="flex gap-2">
    <a href="{{ route($current, array_merge(request()->query(), ['export' => 'pdf'])) }}"
        class="px-3 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700">Exporter PDF</a>
    <a href="{{ route($current, array_merge(request()->query(), ['export' => 'xlsx'])) }}"
        class="px-3 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">Exporter Excel</a>
</div>
