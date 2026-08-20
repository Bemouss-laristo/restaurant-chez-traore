<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Raison d'un mouvement de stock — pour l'audit « où est passée la marchandise ? ».
 *
 * Purchase : achat / réapprovisionnement.
 * Sale     : consommation automatique liée à une vente (via la recette).
 * Waste    : perte, casse, péremption.
 * Manual   : ajustement manuel après inventaire.
 */
enum StockMovementReason: string
{
    case Purchase = 'purchase';
    case Sale = 'sale';
    case Waste = 'waste';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Achat',
            self::Sale => 'Vente',
            self::Waste => 'Perte',
            self::Manual => 'Ajustement manuel',
        };
    }
}
