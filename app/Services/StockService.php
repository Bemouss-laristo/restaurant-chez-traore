<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Point d'entrée UNIQUE pour toute variation de stock.
 *
 * Chaque appel écrit une ligne immuable dans stock_movements ET met à jour
 * la quantité en cache de l'article, le tout dans une transaction atomique
 * avec verrou (lockForUpdate) pour éviter les conditions de course.
 */
final class StockService
{
    /** Entrée de stock (achat, réapprovisionnement). */
    public function addStock(
        StockItem $item,
        float $quantity,
        StockMovementReason $reason = StockMovementReason::Purchase,
        ?User $user = null,
        ?string $note = null,
        ?Model $source = null,
    ): StockMovement {
        return $this->apply($item, StockMovementType::In, $reason, $quantity, $user, $note, $source);
    }

    /** Sortie de stock (perte, ou consommation liée à une vente). */
    public function removeStock(
        StockItem $item,
        float $quantity,
        StockMovementReason $reason = StockMovementReason::Waste,
        ?User $user = null,
        ?string $note = null,
        ?Model $source = null,
    ): StockMovement {
        return $this->apply($item, StockMovementType::Out, $reason, $quantity, $user, $note, $source);
    }

    /**
     * Ajustement d'inventaire : fixe la quantité à la valeur réellement comptée.
     * Le mouvement enregistré porte l'écart (delta) constaté.
     */
    public function adjust(
        StockItem $item,
        float $countedQuantity,
        ?User $user = null,
        ?string $note = null,
    ): StockMovement {
        if ($countedQuantity < 0) {
            throw new InvalidArgumentException('La quantité comptée ne peut pas être négative.');
        }

        return DB::transaction(function () use ($item, $countedQuantity, $user, $note) {
            $item = StockItem::whereKey($item->getKey())->lockForUpdate()->firstOrFail();

            $delta = $countedQuantity - (float) $item->quantity;
            $type = $delta >= 0 ? StockMovementType::In : StockMovementType::Out;

            $item->quantity = $countedQuantity;
            $item->save();

            return $item->movements()->create([
                'user_id' => $user?->id,
                'type' => $type,
                'reason' => StockMovementReason::Manual,
                'quantity' => abs($delta),
                'note' => $note,
            ]);
        });
    }

    /** Cœur commun : applique un mouvement in/out et met à jour la quantité. */
    private function apply(
        StockItem $item,
        StockMovementType $type,
        StockMovementReason $reason,
        float $quantity,
        ?User $user,
        ?string $note,
        ?Model $source,
    ): StockMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('La quantité du mouvement doit être positive.');
        }

        return DB::transaction(function () use ($item, $type, $reason, $quantity, $user, $note, $source) {
            $item = StockItem::whereKey($item->getKey())->lockForUpdate()->firstOrFail();

            $newQuantity = (float) $item->quantity + ($type->sign() * $quantity);
            // On ne descend jamais sous zéro (un stock négatif n'a pas de sens physique).
            $item->quantity = max(0, $newQuantity);
            $item->save();

            $movement = new StockMovement([
                'user_id' => $user?->id,
                'type' => $type,
                'reason' => $reason,
                'quantity' => $quantity,
                'note' => $note,
            ]);
            $movement->stockItem()->associate($item);

            if ($source !== null) {
                $movement->source()->associate($source);
            }

            $movement->save();

            return $movement;
        });
    }
}
