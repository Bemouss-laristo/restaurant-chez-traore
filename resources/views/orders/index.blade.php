<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Commandes en ligne
                @if ($pendingCount > 0)
                    <span class="ms-2 px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">{{ $pendingCount }} nouvelle(s)</span>
                @endif
            </h2>
            <a href="{{ route('order.create') }}" target="_blank" class="text-sm text-indigo-600 hover:underline">Voir la page client ↗</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">{{ session('error') }}</div>
            @endif

            {{-- Commandes actives --}}
            @forelse ($active as $order)
                <div class="bg-white shadow sm:rounded-lg p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-sm text-gray-700">{{ $order->order_number }}</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
                                <span class="text-xs text-gray-400">{{ $order->created_at->format('d/m H:i') }}</span>
                            </div>
                            <div class="mt-1 font-medium text-gray-900">{{ $order->customer_name }}</div>
                            <a href="tel:{{ $order->customer_phone }}" class="text-sm text-indigo-600 hover:underline">📞 {{ $order->customer_phone }}</a>
                            @if ($order->note)
                                <div class="mt-1 text-sm text-amber-700">Note : {{ $order->note }}</div>
                            @endif
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-500">Total</div>
                            <div class="text-lg font-bold text-gray-900">@mru($order->total)</div>
                        </div>
                    </div>

                    <div class="mt-3 border-t border-gray-100 pt-3">
                        @foreach ($order->items as $item)
                            <div class="flex justify-between text-sm py-0.5">
                                <span class="text-gray-800">{{ (int) $item->quantity }} × {{ $item->product->name ?? 'Produit' }}</span>
                                <span class="text-gray-500">@mru($item->line_total)</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        @if ($order->status === \App\Enums\OrderStatus::Nouvelle)
                            <form method="POST" action="{{ route('orders.confirm', $order) }}">
                                @csrf
                                <button class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700">Confirmer</button>
                            </form>
                        @endif

                        {{-- Encaisser (crée la vente) --}}
                        <form method="POST" action="{{ route('orders.checkout', $order) }}" class="flex items-center gap-2">
                            @csrf
                            <select name="payment_method" class="border-gray-300 rounded-md shadow-sm text-sm">
                                @foreach ($paymentMethods as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <button class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-md hover:bg-green-700">Encaisser</button>
                        </form>

                        <form method="POST" action="{{ route('orders.reject', $order) }}"
                            onsubmit="return confirm('Annuler cette commande ?');">
                            @csrf
                            <button class="text-red-600 hover:underline text-sm">Annuler</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="bg-white shadow sm:rounded-lg p-8 text-center text-gray-500">
                    Aucune commande en attente. Les nouvelles commandes des clients apparaîtront ici automatiquement.
                </div>
            @endforelse

            {{-- Historique récent --}}
            @if ($recent->isNotEmpty())
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-800 mb-3">Commandes récentes</h3>
                    <div class="divide-y divide-gray-100">
                        @foreach ($recent as $order)
                            <div class="flex items-center justify-between py-2 text-sm">
                                <span class="font-mono text-gray-600">{{ $order->order_number }}</span>
                                <span class="text-gray-700">{{ $order->customer_name }}</span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
                                <span class="text-gray-500">@mru($order->total)</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        (function () {
            // Bip si une nouvelle commande est arrivée depuis la dernière fois.
            var pending = {{ $pendingCount }};
            var last = parseInt(localStorage.getItem('lastPending') || '0', 10);
            if (pending > last) {
                try {
                    var ctx = new (window.AudioContext || window.webkitAudioContext)();
                    var o = ctx.createOscillator();
                    var g = ctx.createGain();
                    o.connect(g); g.connect(ctx.destination);
                    o.frequency.value = 880; o.type = 'sine';
                    g.gain.setValueAtTime(0.2, ctx.currentTime);
                    o.start();
                    o.stop(ctx.currentTime + 0.4);
                } catch (e) {}
            }
            localStorage.setItem('lastPending', pending);

            // Rafraîchit la page toutes les 25 secondes pour voir les nouvelles commandes.
            setTimeout(function () { location.reload(); }, 25000);
        })();
    </script>
</x-app-layout>
