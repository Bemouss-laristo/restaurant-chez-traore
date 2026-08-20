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
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $onlyLow = $request->boolean('low');

        $items = StockItem::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($onlyLow, fn ($q) => $q->lowStock())
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('stock.index', [
            'items' => $items,
            'search' => $search,
            'onlyLow' => $onlyLow,
            'lowCount' => StockItem::lowStock()->count(),
        ]);
    }

    public function create(): View
    {
        return view('stock.create', ['units' => Unit::options()]);
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
