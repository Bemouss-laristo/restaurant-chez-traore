<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Unités de mesure des articles de stock.
 * Les quantités sont stockées en DECIMAL(12,3) pour gérer grammes et millilitres.
 */
enum Unit: string
{
    case Kilogramme = 'kg';
    case Gramme = 'g';
    case Litre = 'litre';
    case Millilitre = 'ml';
    case Piece = 'piece';

    public function label(): string
    {
        return match ($this) {
            self::Kilogramme => 'Kilogramme (kg)',
            self::Gramme => 'Gramme (g)',
            self::Litre => 'Litre (L)',
            self::Millilitre => 'Millilitre (ml)',
            self::Piece => 'Pièce',
        };
    }

    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $u) => $carry + [$u->value => $u->label()],
            [],
        );
    }
}
