<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Sens d'un mouvement de stock.
 *
 * In         : entrée (achat, réapprovisionnement).
 * Out        : sortie (vente via recette, perte).
 * Adjustment : correction manuelle après inventaire physique.
 */
enum StockMovementType: string
{
    case In = 'in';
    case Out = 'out';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Entrée',
            self::Out => 'Sortie',
            self::Adjustment => 'Ajustement',
        };
    }

    /**
     * Signe appliqué à la quantité pour mettre à jour le stock.
     * La quantité est toujours stockée en positif ; le sens vient d'ici.
     */
    public function sign(): int
    {
        return match ($this) {
            self::In => 1,
            self::Out => -1,
            self::Adjustment => 1, // l'ajustement fixe une valeur, géré à part
        };
    }
}
