<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\StockMovementReason;
use App\Models\CashSession;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class SaleService
{
    public function __construct(private readonly StockService $stock)
    {
    }

    /**
     * Enregistre une vente complète de façon atomique :
     *  - crée la vente et ses lignes (prix figé à l'instant de la vente) ;
     *  - la rattache à la caisse ouverte du caissier ;
     *  - décrémente le stock des ingrédients pour tout plat ayant une recette.
     *
     * @param  list<array{product_id:int, quantity:int}>  $items
     */
    public function record(User $cashier, array $items, PaymentMethod $method, ?CashSession $session): Sale
    {
        return DB::transaction(function () use ($cashier, $items, $method, $session) {
            $products = Product::whereIn('id', array_column($items, 'product_id'))
                ->get()
                ->keyBy('id');

            $sale = new Sale([
                'sale_number' => $this->nextSaleNumber(),
                'sold_at' => now(),
                'payment_method' => $method,
                'subtotal' => 0,
                'total' => 0,
            ]);
            $sale->user()->associate($cashier);
            if ($session !== null) {
                $sale->cashSession()->associate($session);
            }
            $sale->save();

            $total = 0.0;

            foreach ($items as $row) {
                $product = $products[$row['product_id']] ?? null;
                if ($product === null) {
                    continue;
                }

                $quantity = (int) $row['quantity'];
                $unitPrice = (float) $product->sale_price;
                $lineTotal = $unitPrice * $quantity;
                $total += $lineTotal;

                $sale->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);

                $this->consumeRecipe($product, $quantity, $cashier, $sale);
            }

            $sale->update(['subtotal' => $total, 'total' => $total]);

            return $sale;
        });
    }

    /**
     * Retire du stock les ingrédients de la recette du produit.
     * Si le produit n'a pas de recette, ne fait rien (stock non suivi pour ce plat).
     */
    private function consumeRecipe(Product $product, int $soldQuantity, User $user, Sale $sale): void
    {
        $product->loadMissing('recipeItems.stockItem');

        foreach ($product->recipeItems as $recipeItem) {
            $needed = (float) $recipeItem->quantity_needed * $soldQuantity;

            if ($needed <= 0 || $recipeItem->stockItem === null) {
                continue;
            }

            // Jamais bloquant : le service borne le stock à 0 si nécessaire.
            $this->stock->removeStock(
                $recipeItem->stockItem,
                $needed,
                StockMovementReason::Sale,
                $user,
                null,
                $sale,
            );
        }
    }

    /** Numéro lisible du type V-20260805-0001, réinitialisé chaque jour. */
    private function nextSaleNumber(): string
    {
        $today = now();
        $count = Sale::whereDate('sold_at', $today->toDateString())->count();

        return sprintf('V-%s-%04d', $today->format('Ymd'), $count + 1);
    }
}
