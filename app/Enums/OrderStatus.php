<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Statut d'une commande client (à emporter).
 *
 * Nouvelle  : reçue, en attente de confirmation par le personnel.
 * Confirmee : acceptée, en préparation.
 * Terminee  : le client est venu, a payé → une vente a été créée.
 * Annulee   : refusée ou annulée.
 */
enum OrderStatus: string
{
    case Nouvelle = 'nouvelle';
    case Confirmee = 'confirmee';
    case Terminee = 'terminee';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::Nouvelle => 'Nouvelle',
            self::Confirmee => 'Confirmée',
            self::Terminee => 'Terminée',
            self::Annulee => 'Annulée',
        };
    }

    /** Classe Tailwind pour le badge de statut. */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Nouvelle => 'bg-amber-100 text-amber-800',
            self::Confirmee => 'bg-blue-100 text-blue-800',
            self::Terminee => 'bg-green-100 text-green-800',
            self::Annulee => 'bg-gray-200 text-gray-600',
        };
    }
}
