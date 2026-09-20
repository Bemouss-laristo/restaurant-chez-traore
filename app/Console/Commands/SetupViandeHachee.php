<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PaymentMethod;
use App\Models\Product;
use App\Models\StockItem;
use App\Models\Supplier;
use Illuminate\Console\Command;

/**
 * Met en place la chaîne de la viande hachée :
 *   fournisseur (Fabe Cissé) → article de stock → recettes des produits qui en utilisent.
 *
 * Les grammages sont des ESTIMATIONS de départ : pèse cinq portions réelles et
 * corrige-les dans Produits → Modifier → Recette. Une recette fausse de 20 %
 * fausse la marge ET le contrôle des pertes.
 *
 *   php artisan restaurant:viande-hachee
 *   php artisan restaurant:viande-hachee --kebab=0.15 --tacos=0.18
 */
class SetupViandeHachee extends Command
{
    protected $signature = 'restaurant:viande-hachee
        {--fournisseur=Fabe Cissé}
        {--kg-par-jour=2 : quantité livrée chaque jour}
        {--prix-kg=220 : prix du kilo convenu avec le fournisseur}
        {--kebab=0.12 : kg de viande par kebab viande}
        {--tacos=0.15 : kg par tacos viande}
        {--pizza=0.10 : kg par pizza viande}
        {--hamburger=0.12 : kg par hamburger}';

    protected $description = 'Crée le fournisseur de viande hachée, l\'article de stock et les recettes';

    public function handle(): int
    {
        $supplier = Supplier::firstOrCreate(
            ['name' => (string) $this->option('fournisseur')],
            ['note' => 'Viande hachée — abonnement payé d\'avance en début de mois', 'is_active' => true],
        );
        $this->line('  Fournisseur : '.$supplier->name.($supplier->wasRecentlyCreated ? ' (créé)' : ' (déjà présent)'));

        $perDay = (float) $this->option('kg-par-jour');
        $pricePerKg = (float) $this->option('prix-kg');
        $dailyPrice = round($perDay * $pricePerKg, 2);

        $viande = StockItem::firstOrNew(['name' => 'Viande hachée']);
        $created = ! $viande->exists;
        $viande->fill([
            'unit' => 'kg',
            'supplier_id' => $supplier->id,
            // Le mois est payé d'avance, mais chaque livraison reste enregistrée sur son
            // compte : le paiement d'avance met le solde en négatif (une avance) et les
            // livraisons quotidiennes viennent la consommer. Si les livraisons dépassent
            // le mois payé, le solde repasse en dette : l'écart devient visible.
            'default_payment_method' => PaymentMethod::Credit,
            'daily_quantity' => $perDay,
            'agreed_unit_price' => $pricePerKg,
            'is_key' => true,
            'alert_threshold' => 5,
        ]);
        if ($created) {
            $viande->quantity = 0;
            $viande->unit_cost = $pricePerKg;
        }
        $viande->save();
        $this->line('  Article : Viande hachée (kg)'.($created ? ' (créé)' : ' (mis à jour)'));
        $this->line('  Abonnement : '.$this->number($perDay).' kg par jour à '.number_format($pricePerKg, 2, ',', ' ').' MRU le kg = '.number_format($dailyPrice, 0, ',', ' ').' MRU par jour.');

        /** @var array<string, float> $recipes */
        $recipes = [
            'Kebab viande' => (float) $this->option('kebab'),
            'Kebab spécial' => (float) $this->option('kebab'),
            'Tacos viande' => (float) $this->option('tacos'),
            'Pizza viande' => (float) $this->option('pizza'),
            'Hamburger' => (float) $this->option('hamburger'),
        ];

        $missing = [];
        foreach ($recipes as $name => $quantity) {
            $product = Product::where('name', $name)->first();

            if ($product === null) {
                $missing[] = $name;

                continue;
            }

            $product->stockItems()->syncWithoutDetaching([$viande->id => ['quantity_needed' => $quantity]]);
            $this->line('  Recette : '.$product->name.' = '.$quantity.' kg de viande hachée');
        }

        if ($missing !== []) {
            $this->warn('  Produits introuvables (à créer dans le menu Produits) : '.implode(', ', $missing));
        }

        $this->info('Viande hachée reliée à '.$supplier->name.'.');
        $this->line('  En début de mois : Fournisseurs → '.$supplier->name.' → payer '.number_format($dailyPrice * 30, 0, ',', ' ').' MRU (30 jours). Le solde passe en AVANCE.');
        $this->line('  Chaque jour : Achats → « Enregistrer la livraison du jour ». L\'avance baisse, le stock monte.');
        $this->line('  Grammages des recettes à vérifier en pesant cinq portions réelles.');

        return self::SUCCESS;
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', ' '), '0'), ',');
    }
}
