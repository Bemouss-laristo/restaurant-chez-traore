<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Modes de paiement acceptés au restaurant.
 *
 * Règle financière essentielle : seul l'argent liquide (Especes) transite par
 * le tiroir-caisse. Bankily, Sedad et Masrivi arrivent sur des comptes mobiles,
 * donc ils ne doivent JAMAIS entrer dans le calcul de l'écart de caisse.
 */
enum PaymentMethod: string
{
    case Especes = 'especes';
    case Bankily = 'bankily';
    case Sedad = 'sedad';
    case Masrivi = 'masrivi';
    /** Livraison prise à crédit sur un compte fournisseur, payée en fin de mois. */
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Especes => 'Espèces',
            self::Bankily => 'Bankily',
            self::Sedad => 'Sedad',
            self::Masrivi => 'Masrivi',
            self::Credit => 'À crédit (compte fournisseur)',
        };
    }

    /**
     * Ce paiement touche-t-il le tiroir-caisse physique ?
     * Vrai uniquement pour les espèces.
     */
    public function affectsCashDrawer(): bool
    {
        return $this === self::Especes;
    }

    /** Modes de paiement possibles pour une VENTE (jamais à crédit). */
    public static function salesOptions(): array
    {
        return array_reduce(
            array_filter(self::cases(), fn (self $m) => $m !== self::Credit),
            fn (array $carry, self $m) => $carry + [$m->value => $m->label()],
            [],
        );
    }

    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $m) => $carry + [$m->value => $m->label()],
            [],
        );
    }
}
