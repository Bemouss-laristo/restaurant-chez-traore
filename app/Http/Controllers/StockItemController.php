<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StockMovementReason;
use App\Enums\Unit;
use App\Http\Requests\RecordStockMovementRequest;
use App\Http\Requests\StoreStockItemRequest;
use App\Http\Requests\UpdateStockItemRequest;
use App\Models\StockItem;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockItemController extends Controller
{
    /**
     * La liste complète part dans la page : la recherche est alors instantanée,
     * sans aller-retour réseau à chaque lettre tapée. Un restaurant suit quelques
     * dizaines d'articles ; si la liste dépassait un jour le millier, il faudrait
     * revenir à une recherche côté serveur.
     */
    public function index(Request $request): View
    {
        $items = StockItem::query()
            ->with('supplier')
            ->orderBy('name')
            ->get();

        return view('stock.index', [
            'items' => $items,
            'lowCount' => $items->filter(fn (StockItem $item) => $item->isLow())->count(),
        ]);
    }

    public function create(): View
    {
        return view('stock.create', [
            'units' => Unit::options(),
            'suppliers' => \App\Models\Supplier::active()->orderBy('name')->get(),
            'paymentMethods' => \App\Enums\PaymentMethod::options(),
        ]);
    }

    public function store(StoreStockItemRequest $request): RedirectResponse
    {
        StockItem::create($request->validated());

        return redirect()
            ->route('stock-items.index')
            ->with('status', 'Article de stock créé.');
    }

    public function edit(StockItem $stockItem): View
    {
        return view('stock.edit', [
            'item' => $stockItem,
            'units' => Unit::options(),
            'suppliers' => \App\Models\Supplier::active()->orderBy('name')->get(),
            'paymentMethods' => \App\Enums\PaymentMethod::options(),
            'movements' => $stockItem->movements()
                ->with('user')
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }

    public function update(UpdateStockItemRequest $request, StockItem $stockItem): RedirectResponse
    {
        $stockItem->update($request->validated());

        return redirect()
            ->route('stock-items.index')
            ->with('status', 'Article mis à jour.');
    }

    public function destroy(StockItem $stockItem): RedirectResponse
    {
        // On refuse la suppression d'un article déjà utilisé (recettes ou mouvements),
        // pour ne pas casser l'historique. On le laisse plutôt à zéro.
        if ($stockItem->movements()->exists() || $stockItem->products()->exists()) {
            return back()->with('error', "Cet article est utilisé (recette ou mouvements) et ne peut pas être supprimé.");
        }

        $stockItem->delete();

        return back()->with('status', 'Article supprimé.');
    }

    /** Enregistre une entrée / sortie / ajustement via le service dédié. */
    public function movement(RecordStockMovementRequest $request, StockItem $stockItem, StockService $stock): RedirectResponse
    {
        $data = $request->validated();
        $quantity = (float) $data['quantity'];
        $user = $request->user();
        $note = $data['note'] ?? null;

        match ($data['action']) {
            'purchase' => $stock->addStock($stockItem, $quantity, StockMovementReason::Purchase, $user, $note),
            'waste' => $stock->removeStock($stockItem, $quantity, StockMovementReason::Waste, $user, $note),
            'adjustment' => $stock->adjust($stockItem, $quantity, $user, $note),
        };

        return back()->with('status', 'Mouvement de stock enregistré.');
    }
}
