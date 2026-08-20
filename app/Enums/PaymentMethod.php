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

    public function label(): string
    {
        return match ($this) {
            self::Especes => 'Espèces',
            self::Bankily => 'Bankily',
            self::Sedad => 'Sedad',
            self::Masrivi => 'Masrivi',
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

    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $m) => $carry + [$m->value => $m->label()],
            [],
        );
    }
}
