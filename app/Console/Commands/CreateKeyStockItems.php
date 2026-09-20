<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\StockItem;
use Illuminate\Console\Command;

/**
 * Crée (ou met à jour) les articles clés du restaurant avec leur conditionnement
 * d'achat. À lancer une fois : php artisan restaurant:articles-cles
 *
 * Les quantités en stock ne sont JAMAIS touchées : elles ne changent que par un
 * achat, une vente ou un comptage.
 */
class CreateKeyStockItems extends Command
{
    protected $signature = 'restaurant:articles-cles';

    protected $description = 'Crée les articles clés (pain arabe, pain tacos, poulet) avec leur conditionnement';

    /** [nom, unité, conditionnement, unités par conditionnement, seuil d'alerte, coût unitaire de départ] */
    private const ITEMS = [
        ['Pain arabe', 'piece', 'Paquet de 10', 10, 50, 4],
        ['Pain tacos', 'piece', 'Carton (6 sachets × 18)', 108, 100, 0],
        ['Poulet', 'piece', 'Carton de 10', 10, 5, 0],
    ];

    public function handle(): int
    {
        foreach (self::ITEMS as [$name, $unit, $packLabel, $packQuantity, $threshold, $cost]) {
            $item = StockItem::firstOrNew(['name' => $name]);
            $created = ! $item->exists;

            $item->fill([
                'unit' => $unit,
                'pack_label' => $packLabel,
                'pack_quantity' => $packQuantity,
                'is_key' => true,
                'alert_threshold' => $threshold,
            ]);

            if ($created) {
                $item->quantity = 0;
                $item->unit_cost = $cost;
            }

            $item->save();

            $this->line(($created ? '  Créé   ' : '  Mis à jour ').$name.' — '.$packLabel.' = '.$packQuantity.' '.$unit);
        }

        $this->info('Articles clés prêts. Complète leur quantité réelle avec un comptage (Rapports → Réconciliation).');

        return self::SUCCESS;
    }
}
