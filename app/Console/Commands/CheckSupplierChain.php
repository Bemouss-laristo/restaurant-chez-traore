<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PaymentMethod;
use App\Enums\StockMovementReason;
use App\Models\StockItem;
use App\Models\Supplier;
use Illuminate\Console\Command;

/**
 * Dit, en une commande, si la chaîne « article → fournisseur → dette » est bien
 * branchée, et sinon exactement quel maillon manque.
 *
 * Quand une saisie « ne fait rien », c'est presque toujours un maillon absent :
 * l'article n'a pas de fournisseur, ou pas de prix convenu, ou son mode d'achat
 * n'est pas « à crédit ». Chercher ça à l'œil dans l'interface prend dix minutes ;
 * ici c'est immédiat.
 *
 *   php artisan restaurant:verifier
 */
class CheckSupplierChain extends Command
{
    protected $signature = 'restaurant:verifier';

    protected $description = 'Vérifie le lien entre les articles, leurs fournisseurs et les dettes';

    public function handle(): int
    {
        $this->info('ARTICLES RATTACHÉS À UN FOURNISSEUR');
        $this->newLine();

        $items = StockItem::with('supplier')->orderBy('name')->get();
        $problems = 0;

        foreach ($items as $item) {
            $hasSupplier = $item->supplier !== null;
            $hasPrice = (float) $item->agreed_unit_price > 0;
            $onCredit = $item->default_payment_method === PaymentMethod::Credit;

            if (! $hasSupplier && ! $hasPrice) {
                continue;   // article d'achat ponctuel : rien à vérifier
            }

            $this->line('  '.$item->name.' ('.$item->unit->value.')');
            $this->line('    Fournisseur ......... '.($hasSupplier ? $item->supplier->name : 'AUCUN'));
            $this->line('    Prix convenu ........ '.($hasPrice ? number_format((float) $item->agreed_unit_price, 2, ',', ' ').' MRU' : 'AUCUN'));
            $this->line('    Mode d\'achat ........ '.($item->default_payment_method?->label() ?? 'non défini'));
            $this->line('    Quantité en stock ... '.rtrim(rtrim(number_format((float) $item->quantity, 3, ',', ' '), '0'), ','));

            $purchases = $item->movements()
                ->where('reason', StockMovementReason::Purchase->value)
                ->count();
            $this->line('    Entrées enregistrées. '.$purchases);

            if (! $hasSupplier) {
                $this->error('    → La prise du jour n\'apparaîtra pas : rattache un fournisseur à cet article.');
                $problems++;
            } elseif (! $hasPrice) {
                $this->error('    → La prise du jour n\'apparaîtra pas : renseigne le prix convenu.');
                $problems++;
            } elseif (! $onCredit) {
                $this->warn('    → Mode d\'achat différent de « à crédit » : les prises ne créeront pas de dette.');
                $problems++;
            } else {
                $this->line('    → Chaîne complète.');
            }

            $this->newLine();
        }

        $this->info('FOURNISSEURS');
        $this->newLine();

        foreach (Supplier::with('stockItems')->orderBy('name')->get() as $supplier) {
            $balance = $supplier->balance();
            $state = $balance > 0
                ? number_format($balance, 2, ',', ' ').' MRU à payer'
                : ($balance < 0 ? number_format(-$balance, 2, ',', ' ').' MRU d\'avance' : 'solde nul');

            $this->line('  '.$supplier->name);
            $this->line('    Articles ............ '.($supplier->stockItems->pluck('name')->implode(', ') ?: 'aucun'));
            $this->line('    Livraisons saisies .. '.$supplier->deliveries()->count().' (dont '.$supplier->deliveries()->where('payment_method', PaymentMethod::Credit->value)->count().' à crédit)');
            $this->line('    Règlements .......... '.$supplier->payments()->count());
            $this->line('    Solde ............... '.$state);
            $this->newLine();
        }

        if ($problems > 0) {
            $this->warn($problems.' point(s) à corriger ci-dessus (menu Stock → Gérer l\'article).');
        } else {
            $this->info('Tout est branché correctement.');
        }

        return self::SUCCESS;
    }
}
