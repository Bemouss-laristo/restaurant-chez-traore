<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Services\CashSessionService;
use App\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __construct(
        private readonly SaleService $sales,
        private readonly CashSessionService $cash,
    ) {
    }

    /** Écran de vente (POS) : cartes de produits + panier. */
    public function create(Request $request): View
    {
        $products = Product::active()->with('category')->orderBy('name')->get();

        $productsData = $products->map(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'price' => (float) $p->sale_price,
            'category_id' => $p->product_category_id,
            'image' => $p->imageUrl(),
        ])->values();

        return view('sales.create', [
            'productsData' => $productsData,
            'categories' => ProductCategory::orderBy('name')->get(),
            'hasOpenSession' => $this->cash->currentFor($request->user()) !== null,
        ]);
    }

    /** Historique des ventes. */
    public function index(Request $request): View
    {
        $sales = Sale::with('user')
            ->withCount('items')
            ->latest('sold_at')
            ->paginate(20);

        return view('sales.index', ['sales' => $sales]);
    }

    /** Détail d'une vente : produits, quantités, prix. */
    public function show(Sale $sale): View
    {
        $sale->load(['items.product', 'user', 'cashSession']);

        return view('sales.show', ['sale' => $sale]);
    }

    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $sale = $this->sales->record(
            $user,
            $data['items'],
            PaymentMethod::from($data['payment_method']),
            $this->cash->currentFor($user),
        );

        return redirect()
            ->route('sales.create')
            ->with('status', "Vente {$sale->sale_number} enregistrée — total ".number_format((float) $sale->total, 0, ',', ' ').' MRU.');
    }
}
