<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\StockItem;
use Illuminate\Database\Seeder;

/**
 * Ingrédients de démonstration pour composer les recettes.
 * Quantités et coûts (MRU) indicatifs — à ajuster selon la réalité du restaurant.
 */
class StockSeeder extends Seeder
{
    /** [nom, unité, quantité, seuil d'alerte, coût unitaire] */
    private const ITEMS = [
        ['Poulet', 'kg', 20, 5, 350],
        ['Viande hachée', 'kg', 15, 4, 500],
        ['Poisson thiof', 'kg', 10, 3, 400],
        ['Riz', 'kg', 50, 10, 40],
        ['Pâtes spaghetti', 'kg', 20, 5, 60],
        ['Huile', 'litre', 30, 5, 90],
        ['Oignon', 'kg', 25, 5, 30],
        ['Tomate', 'kg', 20, 5, 35],
        ['Pomme de terre', 'kg', 40, 8, 25],
        ['Pain (baguette)', 'piece', 60, 15, 10],
        ['Fromage', 'kg', 8, 2, 700],
        ['Œufs', 'piece', 120, 30, 8],
        ['Farine', 'kg', 25, 5, 45],
        ['Sucre', 'kg', 20, 5, 50],
        ['Lait', 'litre', 20, 5, 60],
        ['Salade (laitue)', 'piece', 30, 8, 25],
        ['Épices (mélange)', 'kg', 5, 1, 300],
        ['Frites surgelées', 'kg', 15, 4, 120],
    ];

    public function run(): void
    {
        foreach (self::ITEMS as [$name, $unit, $qty, $threshold, $cost]) {
            StockItem::updateOrCreate(
                ['name' => $name],
                [
                    'unit' => $unit,
                    'quantity' => $qty,
                    'alert_threshold' => $threshold,
                    'unit_cost' => $cost,
                ],
            );
        }
    }
}
