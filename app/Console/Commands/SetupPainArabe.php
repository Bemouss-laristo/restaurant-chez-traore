<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Unit;
use App\Models\Product;
use App\Models\StockItem;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Met en place la chaîne complète du pain arabe :
 *   fournisseur (Lassana Camara) → article de stock → recettes des produits qui en utilisent.
 *
 * L'unité de mesure est le PAQUET (10 pains par défaut), parce que c'est ainsi
 * qu'on achète et qu'on compte : « 10 » au comptage = 10 paquets = 100 pains.
 * Un sandwich consomme donc 0,1 paquet.
 *
 *   php artisan restaurant:pain-arabe
 *   php artisan restaurant:pain-arabe --pains-par-paquet=10 --prix-paquet=40
 */
class SetupPainArabe extends Command
{
    protected $signature = 'restaurant:pain-arabe
        {--fournisseur=Lassana Camara}
        {--pains-par-paquet=10 : nombre de pains dans un paquet}
        {--prix-paquet=40 : prix d\'achat d\'un paquet, utilisé seulement à la création}';

    protected $description = 'Crée le fournisseur du pain arabe, l\'article (en paquets) et les recettes';

    /** Produits qui consomment 1 pain chacun. */
    private const PRODUCTS = ['Kebab poulet', 'Kebab viande', 'Kebab spécial', 'Chawarma'];

    public function handle(): int
    {
        $perPack = max(1, (int) $this->option('pains-par-paquet'));
        $packPrice = (float) $this->option('prix-paquet');

        $supplier = Supplier::firstOrCreate(
            ['name' => (string) $this->option('fournisseur')],
            ['note' => 'Pain arabe — livré au fil du mois, facturé à la fin', 'is_active' => true],
        );
        $this->line('  Fournisseur : '.$supplier->name.($supplier->wasRecentlyCreated ? ' (créé)' : ' (déjà présent)'));

        $pain = StockItem::firstOrNew(['name' => 'Pain arabe']);
        $created = ! $pain->exists;

        // Article déjà suivi à la pièce : on convertit tout en paquets, sans rien perdre.
        $converted = ! $created && $pain->unit === Unit::Piece;
        if ($converted) {
            $pain->quantity = round((float) $pain->quantity / $perPack, 3);
            $pain->unit_cost = round((float) $pain->unit_cost * $perPack, 2);
            DB::table('recipe_items')
                ->where('stock_item_id', $pain->id)
                ->update(['quantity_needed' => DB::raw('quantity_needed / '.$perPack)]);
            $this->warn('  Conversion : quantités et recettes passées de la pièce au paquet (÷ '.$perPack.').');
        }

        $pain->fill([
            'unit' => Unit::Paquet,
            'supplier_id' => $supplier->id,
            // On achète directement en paquets : pas de conversion supplémentaire.
            'pack_label' => 'Paquet de '.$perPack.' pains',
            'pack_quantity' => null,
            'is_key' => true,
            'alert_threshold' => 5,
        ]);

        if ($created) {
            $pain->quantity = 0;
            $pain->unit_cost = $packPrice;
        }

        $pain->save();
        $this->line('  Article : Pain arabe — unité = paquet ('.$perPack.' pains), coût actuel '.number_format((float) $pain->unit_cost, 2, ',', ' ').' MRU le paquet');

        // 1 pain = 1 / nombre de pains par paquet.
        $needed = round(1 / $perPack, 3);
        $missing = [];

        foreach (self::PRODUCTS as $name) {
            $product = Product::where('name', $name)->first();

            if ($product === null) {
                $missing[] = $name;

                continue;
            }

            $product->stockItems()->syncWithoutDetaching([$pain->id => ['quantity_needed' => $needed]]);
            $this->line('  Recette : '.$product->name.' = '.$needed.' paquet (1 pain)');
        }

        if ($missing !== []) {
            $this->warn('  Produits introuvables (à créer dans le menu Produits) : '.implode(', ', $missing));
        }

        $this->info('Pain arabe suivi en paquets. Au comptage du soir, saisis le nombre de PAQUETS (10,5 = 10 paquets et 5 pains).');

        return self::SUCCESS;
    }
}
