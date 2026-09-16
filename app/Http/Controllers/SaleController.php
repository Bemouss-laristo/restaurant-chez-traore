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
use RuntimeException;

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
        $sales = Sale::with(['user', 'cashSession'])
            ->withCount('items')
            ->latest('sold_at')
            ->paginate(20);

        return view('sales.index', ['sales' => $sales]);
    }

    /** Détail d'une vente : produits, quantités, prix. */
    public function show(Request $request, Sale $sale): View
    {
        $sale->load(['items.product', 'user', 'cashSession', 'cancelledBy']);

        return view('sales.show', [
            'sale' => $sale,
            'canCancel' => $sale->canBeCancelledBy($request->user()),
            'order' => \App\Models\Order::where('sale_id', $sale->id)->first(),
        ]);
    }

    /** Ticket 80 mm imprimable (auto-impression thermique). */
    public function receipt(Sale $sale): View
    {
        $sale->load(['items.product', 'user']);

        return view('sales.receipt', [
            'sale' => $sale,
            'order' => \App\Models\Order::where('sale_id', $sale->id)->first(),
        ]);
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
            ->with('status', "Vente {$sale->sale_number} enregistrée — total ".number_format((float) $sale->total, 0, ',', ' ').' MRU.')
            ->with('printSaleId', $sale->id);
    }

    /**
     * Annule une vente encaissée (le client a annulé). Motif obligatoire :
     * l'annulation reste visible par le gérant et l'admin.
     */
    public function cancel(Request $request, Sale $sale): RedirectResponse
    {
        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:3', 'max:255'],
        ], [
            'cancellation_reason.required' => "Indique le motif de l'annulation.",
            'cancellation_reason.min' => 'Le motif doit faire au moins 3 caractères.',
        ]);

        if (! $sale->canBeCancelledBy($request->user())) {
            return back()->with('error', $sale->isCancelled()
                ? 'Cette vente est déjà annulée.'
                : "Tu ne peux pas annuler cette vente (autre journée ou caisse déjà clôturée). Demande au gérant.");
        }

        try {
            $this->sales->cancel($sale, $request->user(), $data['cancellation_reason']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sales.show', $sale)
            ->with('status', "Vente {$sale->sale_number} annulée : elle est retirée de la caisse et des rapports, et le stock a été remis.");
    }
}
