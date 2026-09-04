<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PublicOrderController extends Controller
{
    public function __construct(private readonly OrderService $orders)
    {
    }

    /** Page publique de commande : menu + panier. */
    public function create(): View
    {
        $products = Product::active()->with('category')->orderBy('name')->get();

        $productsData = $products->map(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'price' => (float) $p->sale_price,
            'category_id' => $p->product_category_id,
            'image' => $p->imageUrl(),
        ])->values();

        return view('orders.public.create', [
            'productsData' => $productsData,
            'categories' => ProductCategory::orderBy('name')->get(),
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $order = $this->orders->createFromCart(
            $data['items'],
            $data['customer_name'],
            $data['customer_phone'],
            $data['note'] ?? null,
        );

        return redirect()->route('order.thanks')->with('orderInfo', [
            'number' => $order->order_number,
            'total' => (float) $order->total,
            'name' => $order->customer_name,
        ]);
    }

    /** Page de confirmation après l'envoi d'une commande. */
    public function thanks(): View|RedirectResponse
    {
        $info = session('orderInfo');

        if (! $info) {
            return redirect()->route('order.create');
        }

        return view('orders.public.thanks', ['info' => $info]);
    }
}
