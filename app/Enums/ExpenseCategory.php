<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Catégories de dépenses du restaurant.
 * Les salaires sont gérés ici, comme une dépense simple (choix validé).
 */
enum ExpenseCategory: string
{
    case AchatMarchandises = 'achat_marchandises';
    case Eau = 'eau';
    case Electricite = 'electricite';
    case Gaz = 'gaz';
    case Salaires = 'salaires';
    case Transport = 'transport';
    case Divers = 'divers';

    public function label(): string
    {
        return match ($this) {
            self::AchatMarchandises => 'Achat marchandises',
            self::Eau => 'Eau',
            self::Electricite => 'Électricité',
            self::Gaz => 'Gaz',
            self::Salaires => 'Salaires',
            self::Transport => 'Transport',
            self::Divers => 'Divers',
        };
    }

    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $c) => $carry + [$c->value => $c->label()],
            [],
        );
    }
}
