<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Les trois rôles de l'application.
 *
 * Admin    : accès total (utilisateurs, produits, stock, rapports, config).
 * Gerant   : gestion opérationnelle (produits, stock, dépenses, rapports) sans admin.
 * Caissier : ventes et caisse du quotidien uniquement.
 */
enum Role: string
{
    case Admin = 'admin';
    case Gerant = 'gerant';
    case Caissier = 'caissier';

    /** Libellé affiché dans l'interface. */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Gerant => 'Gérant',
            self::Caissier => 'Caissier',
        };
    }

    /** Liste [valeur => libellé] pratique pour les menus déroulants. */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $role) => $carry + [$role->value => $role->label()],
            [],
        );
    }
}
