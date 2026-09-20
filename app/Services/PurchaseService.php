<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Enums\StockMovementReason;
use App\Models\CashSession;
use App\Models\Expense;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Un achat = UNE seule saisie qui fait DEUX choses, toujours ensemble :
 *   1. l'argent sort  → une dépense « Achat marchandises » ;
 *   2. la marchandise entre → un mouvement d'entrée sur l'article de stock.
 *
 * C'est ce lien qui permet de comparer ce qui a été ACHETÉ à ce qui a été VENDU.
 * Le coût unitaire de l'article est recalculé en prix moyen pondéré (PMP).
 */
final class PurchaseService
{
    public function __construct(private readonly StockService $stock)
    {
    }

    /**
     * @param  list<array{stock_item_id:int, quantity:float, pack:bool, total_price:float}>  $lines
     */
    public function record(
        User $user,
        array $lines,
        PaymentMethod $method,
        ?Supplier $supplier,
        string $spentAt,
        ?CashSession $session = null,
    ): Expense {
        return DB::transaction(function () use ($user, $lines, $method, $supplier, $spentAt, $session) {
            $items = StockItem::whereIn('id', array_column($lines, 'stock_item_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $total = 0.0;
            $descriptions = [];
            $prepared = [];

            foreach ($lines as $line) {
                $item = $items[$line['stock_item_id']] ?? null;
                if ($item === null) {
                    continue;
                }

                $entered = (float) $line['quantity'];
                $isPack = (bool) ($line['pack'] ?? false);
                $perPack = $item->unitsPerPack();
                // Saisie en cartons / sachets : on convertit en unités de stock.
                $quantity = $isPack && $perPack !== null ? $entered * $perPack : $entered;

                if ($quantity <= 0) {
                    continue;
                }

                $linePrice = round((float) $line['total_price'], 2);
                $unitCost = round($linePrice / $quantity, 2);
                $total += $linePrice;

                $label = $isPack && $perPack !== null
                    ? sprintf('%s × %s (%s %s)', $this->number($entered), $item->pack_label ?: 'conditionnement', $this->number($quantity), $item->unit->value)
                    : sprintf('%s %s', $this->number($quantity), $item->unit->value);

                $descriptions[] = '• '.$item->name.' — '.$label.' — '.number_format($linePrice, 0, ',', ' ').' MRU';
                $prepared[] = [$item, $quantity, $unitCost, $linePrice];
            }

            $expense = Expense::create([
                'user_id' => $user->id,
                'cash_session_id' => $method->affectsCashDrawer() ? $session?->id : null,
                'supplier_id' => $supplier?->id,
                'expense_category' => ExpenseCategory::AchatMarchandises,
                'amount' => round($total, 2),
                'description' => ($supplier ? 'Fournisseur : '.$supplier->name."\n" : '').implode("\n", $descriptions),
                'payment_method' => $method,
                'spent_at' => Carbon::parse($spentAt),
            ]);

            foreach ($prepared as [$item, $quantity, $unitCost, $linePrice]) {
                // Prix moyen pondéré : l'ancien stock et le nouvel achat sont mélangés.
                $oldQuantity = max(0, (float) $item->quantity);
                $oldValue = $oldQuantity * (float) $item->unit_cost;
                $newCost = round(($oldValue + $linePrice) / ($oldQuantity + $quantity), 2);

                $this->stock->addStock(
                    $item,
                    $quantity,
                    StockMovementReason::Purchase,
                    $user,
                    'Achat'.($supplier ? ' — '.$supplier->name : ''),
                    $expense,
                    $unitCost,
                );

                $item->refresh()->update(['unit_cost' => $newCost]);
            }

            return $expense;
        });
    }

    /**
     * Annule un achat saisi par erreur : le stock ajouté est retiré, la dépense
     * (et donc la dette du fournisseur) disparaît.
     *
     * On SUPPRIME les mouvements d'entrée au lieu d'ajouter un mouvement de sortie.
     * Une entrée qui n'aurait jamais dû exister doit disparaître du contrôle matière,
     * sinon la colonne « acheté » resterait gonflée et le manquant serait faux.
     */
    public function revert(Expense $expense): void
    {
        DB::transaction(function () use ($expense) {
            foreach ($expense->stockMovements()->with('stockItem')->get() as $movement) {
                $item = StockItem::whereKey($movement->stock_item_id)->lockForUpdate()->first();

                if ($item !== null) {
                    // Peut descendre sous zéro si la marchandise a déjà été « vendue » :
                    // c'est la réalité, et le comptage du soir remettra les compteurs droits.
                    $item->quantity = (float) $item->quantity - (float) $movement->quantity;
                    $item->save();
                }

                $movement->delete();
            }

            $expense->delete();
        });
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', ' '), '0'), ',');
    }
}
