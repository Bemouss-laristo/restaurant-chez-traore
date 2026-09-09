<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Models\StockItem;
use App\Services\StockService;
use App\Support\BusinessDay;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Réconciliation d'inventaire : compare le stock THÉORIQUE (ce que l'appli
 * calcule à partir des ventes enregistrées) au stock RÉEL compté à la main.
 * L'écart révèle les fuites (ventes non tapées, pertes, vols).
 */
class StockReconciliationController extends Controller
{
    public function index(): View
    {
        return view('stock.reconciliation', [
            'rows' => $this->context(),
            'results' => null,
            'dateLabel' => Carbon::parse(BusinessDay::today())->format('d/m/Y'),
        ]);
    }

    public function store(Request $request, StockService $stock): View
    {
        $validated = $request->validate([
            'counted' => ['required', 'array'],
            'counted.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user = $request->user();
        $results = collect();

        foreach ($validated['counted'] as $id => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $item = StockItem::find($id);
            if ($item === null) {
                continue;
            }

            $theoretical = (float) $item->quantity;
            $counted = (float) $value;
            $ecart = $counted - $theoretical;

            // Réaligne le stock théorique sur la réalité comptée et journalise l'écart.
            $stock->adjust($item, $counted, $user, "Réconciliation d'inventaire");

            $results->push([
                'name' => $item->name,
                'unit' => $item->unit->value,
                'theoretical' => $theoretical,
                'counted' => $counted,
                'ecart' => $ecart,
                'value' => $ecart * (float) $item->unit_cost,
            ]);
        }

        return view('stock.reconciliation', [
            'rows' => $this->context(),
            'results' => $results,
            'dateLabel' => Carbon::parse(BusinessDay::today())->format('d/m/Y'),
        ]);
    }

    /** Théorique + entrées/sorties de la journée commerciale, par article. */
    private function context(): Collection
    {
        [$start, $end] = BusinessDay::window(BusinessDay::today());

        return StockItem::orderBy('name')->get()->map(function (StockItem $item) use ($start, $end) {
            $saleOut = (float) $item->movements()
                ->where('type', StockMovementType::Out->value)
                ->where('reason', StockMovementReason::Sale->value)
                ->whereBetween('created_at', [$start, $end])
                ->sum('quantity');

            $purchaseIn = (float) $item->movements()
                ->where('type', StockMovementType::In->value)
                ->where('reason', StockMovementReason::Purchase->value)
                ->whereBetween('created_at', [$start, $end])
                ->sum('quantity');

            return [
                'item' => $item,
                'saleOut' => $saleOut,
                'purchaseIn' => $purchaseIn,
            ];
        });
    }
}
