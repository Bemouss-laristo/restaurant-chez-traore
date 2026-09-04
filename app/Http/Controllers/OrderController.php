<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\CashSessionService;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly SaleService $sales,
        private readonly CashSessionService $cash,
    ) {
    }

    /** File des commandes pour le personnel. */
    public function index(): View
    {
        return view('orders.index', [
            'active' => Order::active()->with('items.product')->latest()->get(),
            'recent' => Order::whereIn('status', [OrderStatus::Terminee, OrderStatus::Annulee])
                ->with('items')
                ->latest()
                ->limit(15)
                ->get(),
            'pendingCount' => Order::pending()->count(),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function confirm(Request $request, Order $order): RedirectResponse
    {
        if ($order->status === OrderStatus::Nouvelle) {
            $order->update(['status' => OrderStatus::Confirmee, 'handled_by' => $request->user()->id]);
        }

        return back()->with('status', "Commande {$order->order_number} confirmée.");
    }

    public function reject(Request $request, Order $order): RedirectResponse
    {
        if (in_array($order->status, [OrderStatus::Nouvelle, OrderStatus::Confirmee], true)) {
            $order->update(['status' => OrderStatus::Annulee, 'handled_by' => $request->user()->id]);
        }

        return back()->with('status', "Commande {$order->order_number} annulée.");
    }

    /** Encaisse une commande : crée la vente (stock + rapports + caisse). */
    public function checkout(Request $request, Order $order): RedirectResponse
    {
        $request->validate(['payment_method' => ['required', Rule::enum(PaymentMethod::class)]]);

        if ($order->status === OrderStatus::Terminee) {
            return back()->with('error', 'Cette commande a déjà été encaissée.');
        }

        $user = $request->user();
        $items = $order->items->map(fn ($i) => [
            'product_id' => $i->product_id,
            'quantity' => (int) $i->quantity,
        ])->all();

        $sale = $this->sales->record(
            $user,
            $items,
            PaymentMethod::from($request->input('payment_method')),
            $this->cash->currentFor($user),
        );

        $order->update([
            'status' => OrderStatus::Terminee,
            'sale_id' => $sale->id,
            'handled_by' => $user->id,
        ]);

        return back()->with('status', "Commande {$order->order_number} encaissée — vente {$sale->sale_number}.");
    }

    /** Nombre de commandes en attente (pour le badge / l'alerte en direct). */
    public function count(): JsonResponse
    {
        return response()->json(['pending' => Order::pending()->count()]);
    }
}
