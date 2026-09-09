<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Charge le menu réel de « Chez Traoré ».
 * Prix en MRU. Le coût estimé est laissé à 0 : il sera affiné plus tard,
 * soit à la main, soit calculé automatiquement à partir des recettes.
 */
class MenuSeeder extends Seeder
{
    /**
     * Menu groupé par catégorie : ['Catégorie' => [['nom', prix_de_vente], ...]].
     */
    private const MENU = [
        'Plats' => [
            ['Poulet complet', 300],
            ['Demi poulet', 150],
            ['Brochettes poulet', 240],
            ['Brochettes viande', 250],
            ['Escalope', 250],
            ['Spaghettis bolognaise', 300],
            ['Complet pané', 400],
            ['Demi poulet pané', 200],
            ['Cordons bleu poulet', 160],
            ['Filet de poisson', 130],
            ['Spaghettis poulet', 220],
            ['Spaghettis italien', 180],
            ['Poulet à la crème', 250],
            ['Kefta', 130],
            ['Thiof grillé', 250],
            ['Émincé de poulet', 170],
            ['Salade mix', 70],
        ],
        'Snacks' => [
            ['Pizza viande', 200],
            ['Pizza poulet', 200],
            ['Pizza thon', 180],
            ['Hamburger', 120],
            ['Tacos poulet', 140],
            ['Tacos viande', 140],
            ['Kebab poulet', 50],
            ['Kebab viande', 50],
            ['Sandwich', 50],
            ['Chawarma', 80],
        ],
        'Boissons' => [
            ['Milk-shake fraise', 50],
            ['Milk-shake chocolat', 50],
            ['Milk-shake banane', 50],
            ['Mango', 50],
            ['Mojito', 50],
            ['Coca', 20],
            ['Café au lait', 30],
            ['Taï Traoré', 40],
        ],
        'Desserts' => [
            ['Gâteau (grand)', 800],
            ['Gâteau (moyen)', 600],
            ['Gâteau', 300],
            ['Gâteau tranche', 30],
            ['Keki cake', 100],
            ['Crème glacée', 40],
            ['Cupcake', 20],
            ['Coco', 10],
            ['Madeleine', 5],
            ['Salade de fruits', 80],
        ],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            foreach (self::MENU as $categoryName => $products) {
                $category = ProductCategory::firstOrCreate(['name' => $categoryName]);

                foreach ($products as [$name, $salePrice]) {
                    Product::updateOrCreate(
                        [
                            'product_category_id' => $category->id,
                            'name' => $name,
                        ],
                        [
                            'sale_price' => $salePrice,
                            'estimated_cost' => 0,
                            'is_active' => true,
                        ],
                    );
                }
            }
        });
    }
}
