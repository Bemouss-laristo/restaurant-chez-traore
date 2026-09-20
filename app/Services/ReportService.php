<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Models\BalanceSnapshot;
use App\Models\Budget;
use App\Models\Expense;
use App\Models\Staff;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\StockMovement;
use App\Models\Sale;
use App\Models\StockItem;
use App\Support\BusinessDay;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Tous les calculs suivent la « journée commerciale » (19h → 5h) :
 *  - les VENTES sont rattachées par leur horaire réel (fenêtre décalée) ;
 *  - les DÉPENSES sont datées à la main, donc rattachées par leur date directe ;
 *  - les ventes ANNULÉES ne comptent jamais.
 */
final class ReportService
{
    /** Rapport d'une journée commerciale. */
    public function daily(CarbonInterface $date): array
    {
        $d = $date->toDateString();
        [$start, $end] = BusinessDay::window($d);

        $salesTotal = (float) Sale::valid()->whereBetween('sold_at', [$start, $end])->sum('total');
        $orders = Sale::valid()->whereBetween('sold_at', [$start, $end])->count();
        $expensesTotal = (float) Expense::whereDate('spent_at', $d)->sum('amount');

        return [
            'salesTotal' => $salesTotal,
            'orders' => $orders,
            'expensesTotal' => $expensesTotal,
            'profit' => $salesTotal - $expensesTotal,
            'byPayment' => Sale::valid()->whereBetween('sold_at', [$start, $end])
                ->selectRaw('payment_method, SUM(total) as total')
                ->groupBy('payment_method')
                ->pluck('total', 'payment_method'),
            'byCategory' => Expense::whereDate('spent_at', $d)
                ->selectRaw('expense_category, SUM(amount) as total')
                ->groupBy('expense_category')
                ->pluck('total', 'expense_category'),
            // Top 5 pour l'aperçu, et la liste COMPLÈTE de tout ce qui a été vendu.
            'topProducts' => $this->productsSoldBetween($start, $end, 5),
            'productsSold' => $this->productsSoldBetween($start, $end),
            'cancelled' => $this->cancelledBetween($start, $end),
            'expensesList' => Expense::with('user')->whereDate('spent_at', $d)->orderBy('created_at')->get(),
        ];
    }

    /** Rapport d'une semaine (7 journées commerciales). */
    public function weekly(CarbonInterface $start): array
    {
        $start = $start->copy()->startOfDay();

        $rows = collect(range(0, 6))->map(function (int $i) use ($start) {
            $day = $start->copy()->addDays($i);
            $d = $day->toDateString();
            [$ws, $we] = BusinessDay::window($d);

            $sales = (float) Sale::valid()->whereBetween('sold_at', [$ws, $we])->sum('total');
            $expenses = (float) Expense::whereDate('spent_at', $d)->sum('amount');

            return [
                'date' => $day,
                'label' => $day->translatedFormat('D d/m'),
                'sales' => $sales,
                'expenses' => $expenses,
                'profit' => $sales - $expenses,
            ];
        });

        $withSales = $rows->filter(fn ($r) => $r['sales'] > 0);

        return [
            'start' => $start,
            'end' => $start->copy()->addDays(6),
            'rows' => $rows,
            'totals' => [
                'sales' => $rows->sum('sales'),
                'expenses' => $rows->sum('expenses'),
                'profit' => $rows->sum('profit'),
            ],
            'cancelled' => $this->cancelledBetween(
                BusinessDay::window($start->toDateString())[0],
                BusinessDay::window($start->copy()->addDays(6)->toDateString())[1],
            ),
            'best' => $withSales->sortByDesc('sales')->first(),
            'worst' => $withSales->sortBy('sales')->first(),
        ];
    }

    /** Rapport d'un mois (par journée commerciale). */
    public function monthly(int $year, int $month): array
    {
        $first = Carbon::create($year, $month, 1)->startOfMonth();
        $last = $first->copy()->endOfMonth();
        $hour = BusinessDay::startHour();

        [$rangeStart] = BusinessDay::window($first->toDateString());
        [, $rangeEnd] = BusinessDay::window($last->toDateString());

        // Ventes agrégées par journée commerciale (date décalée de l'heure de reset).
        // $hour vient de la config (entier de confiance), on peut l'insérer directement.
        $salesByDay = Sale::valid()->whereBetween('sold_at', [$rangeStart, $rangeEnd])
            ->selectRaw("DATE(sold_at - INTERVAL {$hour} HOUR) as d, SUM(total) as total")
            ->groupBy('d')
            ->pluck('total', 'd');

        $perDay = collect(range(1, $first->daysInMonth))->map(function (int $day) use ($first, $salesByDay) {
            $key = $first->copy()->day($day)->toDateString();

            return ['day' => $day, 'sales' => (float) ($salesByDay[$key] ?? 0)];
        });

        $monthStart = $first->copy()->startOfDay();
        $monthEnd = $last->copy()->endOfDay();
        $salesTotal = (float) Sale::valid()->whereBetween('sold_at', [$rangeStart, $rangeEnd])->sum('total');
        $expensesTotal = (float) Expense::whereBetween('spent_at', [$monthStart, $monthEnd])->sum('amount');

        return [
            'start' => $first,
            'perDay' => $perDay,
            'salesTotal' => $salesTotal,
            'expensesTotal' => $expensesTotal,
            'profit' => $salesTotal - $expensesTotal,
            'byCategory' => Expense::whereBetween('spent_at', [$monthStart, $monthEnd])
                ->selectRaw('expense_category, SUM(amount) as total')
                ->groupBy('expense_category')
                ->pluck('total', 'expense_category'),
            'topProducts' => $this->productsSoldBetween($rangeStart, $rangeEnd, 5),
            // Liste COMPLÈTE des produits vendus du mois, avec CA, coût matière et marge.
            'productsSold' => $this->productsSold($first->toDateString(), $last->toDateString()),
            'cancelled' => $this->cancelledBetween($rangeStart, $rangeEnd),
        ];
    }

    /**
     * Contrôle du stock d'une journée : par article, acheté (entrées),
     * vendu/sorti (sorties) et reste actuel.
     */
    public function stockControl(string $date): Collection
    {
        [$start, $end] = BusinessDay::window($date);

        return StockItem::orderBy('name')->get()->map(function (StockItem $item) use ($start, $end) {
            $in = (float) $item->movements()
                ->where('type', 'in')
                ->where('reason', '!=', 'sale_cancelled')
                ->whereBetween('created_at', [$start, $end])
                ->sum('quantity');
            // Un retour en stock après annulation de vente annule la sortie correspondante.
            $returned = (float) $item->movements()
                ->where('type', 'in')
                ->where('reason', 'sale_cancelled')
                ->whereBetween('created_at', [$start, $end])
                ->sum('quantity');
            $out = max(0, (float) $item->movements()
                ->where('type', 'out')
                ->whereBetween('created_at', [$start, $end])
                ->sum('quantity') - $returned);

            return ['item' => $item, 'in' => $in, 'out' => $out];
        });
    }

    /** Produits vendus entre deux instants, triés par quantité (liste complète si $limit null). */
    private function productsSoldBetween(Carbon $start, Carbon $end, ?int $limit = null): Collection
    {
        $query = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereBetween('sales.sold_at', [$start, $end])
            ->whereNull('sales.cancelled_at')
            ->groupBy('products.id', 'products.name')
            ->select(
                'products.name',
                DB::raw('SUM(sale_items.quantity) as qty'),
                DB::raw('SUM(sale_items.line_total) as revenue'),
            )
            ->orderByDesc('qty');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Ventes annulées sur la période (rattachées au moment de la vente) :
     * nombre, montant retiré et détail avec le motif, pour le gérant et l'admin.
     *
     * @return array{count:int, total:float, rows:Collection}
     */
    private function cancelledBetween(Carbon $start, Carbon $end): array
    {
        $sales = Sale::query()
            ->whereNotNull('cancelled_at')
            ->whereBetween('sold_at', [$start, $end])
            ->with(['user', 'cancelledBy'])
            ->orderBy('sold_at')
            ->get();

        $orders = \App\Models\Order::whereIn('sale_id', $sales->pluck('id'))->get()->keyBy('sale_id');

        return [
            'count' => $sales->count(),
            'total' => (float) $sales->sum('total'),
            'rows' => $sales->map(fn (Sale $sale) => [
                'sale' => $sale,
                'customer' => $orders[$sale->id]->customer_name ?? null,
            ]),
        ];
    }

    /**
     * CONTRÔLE MATIÈRE — le cœur du contrôle des entrées et sorties.
     *
     * Pour chaque article, sur la période : stock de départ, ce qui a été ACHETÉ,
     * ce que les ventes auraient dû CONSOMMER (via les recettes), les pertes et
     * écarts constatés aux comptages, et le stock de fin — en quantité ET en MRU.
     *
     * L'écart est l'information clé : acheté − consommé par les ventes − reste
     * = ce qui a disparu sans être vendu (offert, jeté, volé, mal saisi).
     */
    public function material(string $from, string $to): array
    {
        [$start] = BusinessDay::window($from);
        [, $end] = BusinessDay::window($to);

        $rows = StockItem::orderBy('name')->get()->map(function (StockItem $item) use ($start, $end) {
            $sum = function (array $filters) use ($item, $start, $end) {
                $query = $item->movements()->whereBetween('created_at', [$start, $end]);
                foreach ($filters as $column => $value) {
                    $query->where($column, $value);
                }
                $rows = $query->get(['quantity', 'unit_cost']);

                return [
                    'qty' => (float) $rows->sum('quantity'),
                    'value' => (float) $rows->sum(fn (StockMovement $m) => (float) $m->quantity * (float) ($m->unit_cost ?? $item->unit_cost)),
                ];
            };

            $purchases = $sum(['type' => StockMovementType::In->value, 'reason' => StockMovementReason::Purchase->value]);
            $sold = $sum(['type' => StockMovementType::Out->value, 'reason' => StockMovementReason::Sale->value]);
            $returned = $sum(['type' => StockMovementType::In->value, 'reason' => StockMovementReason::SaleCancelled->value]);
            $waste = $sum(['type' => StockMovementType::Out->value, 'reason' => StockMovementReason::Waste->value]);
            $adjustOut = $sum(['type' => StockMovementType::Out->value, 'reason' => StockMovementReason::Manual->value]);
            $adjustIn = $sum(['type' => StockMovementType::In->value, 'reason' => StockMovementReason::Manual->value]);

            // Ventes nettes des annulations.
            $consumedQty = max(0, $sold['qty'] - $returned['qty']);
            $consumedValue = max(0, $sold['value'] - $returned['value']);

            // Stock de fin : quantité actuelle, corrigée des mouvements postérieurs à la période.
            $after = $item->movements()->where('created_at', '>=', $end)->get(['type', 'quantity']);
            $finalQty = (float) $item->quantity;
            foreach ($after as $movement) {
                $finalQty -= $movement->type->sign() * (float) $movement->quantity;
            }

            $inQty = $purchases['qty'] + $returned['qty'] + $adjustIn['qty'];
            $outQty = $sold['qty'] + $waste['qty'] + $adjustOut['qty'];
            $openingQty = $finalQty - $inQty + $outQty;

            // Manque = écarts constatés aux comptages + pertes déclarées.
            $missingQty = $adjustOut['qty'] + $waste['qty'] - $adjustIn['qty'];
            $missingValue = $adjustOut['value'] + $waste['value'] - $adjustIn['value'];

            $reference = $openingQty + $purchases['qty'];

            return [
                'item' => $item,
                'openingQty' => $openingQty,
                'purchaseQty' => $purchases['qty'],
                'purchaseValue' => $purchases['value'],
                'consumedQty' => $consumedQty,
                'consumedValue' => $consumedValue,
                'missingQty' => $missingQty,
                'missingValue' => $missingValue,
                'finalQty' => $finalQty,
                'finalValue' => $finalQty * (float) $item->unit_cost,
                'lossRate' => $reference > 0 ? ($missingQty / $reference) * 100 : 0.0,
            ];
        });

        return [
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'totals' => [
                'purchases' => (float) $rows->sum('purchaseValue'),
                'consumed' => (float) $rows->sum('consumedValue'),
                'missing' => (float) $rows->sum('missingValue'),
                'stock' => (float) $rows->sum('finalValue'),
            ],
        ];
    }

    /**
     * Tous les produits vendus sur une période, avec chiffre d'affaires,
     * coût matière estimé et marge — pour voir ce qui rapporte vraiment.
     */
    public function productsSold(string $from, string $to): Collection
    {
        [$start] = BusinessDay::window($from);
        [, $end] = BusinessDay::window($to);

        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereBetween('sales.sold_at', [$start, $end])
            ->whereNull('sales.cancelled_at')
            ->groupBy('products.id', 'products.name')
            ->select(
                'products.name',
                DB::raw('SUM(sale_items.quantity) as qty'),
                DB::raw('SUM(sale_items.line_total) as revenue'),
                DB::raw('SUM(sale_items.quantity * products.estimated_cost) as cost'),
                DB::raw('SUM(sale_items.line_total) - SUM(sale_items.quantity * products.estimated_cost) as margin'),
            )
            ->orderByDesc('revenue')
            ->get();
    }

    /**
     * RAPPORT DES ACHATS sur une période : total, détail par jour, par fournisseur
     * et par article (quantités réelles issues des mouvements de stock).
     */
    public function purchases(string $from, string $to): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        $expenses = Expense::with('supplier')
            ->where('expense_category', ExpenseCategory::AchatMarchandises->value)
            ->whereBetween('spent_at', [$start, $end])
            ->orderBy('spent_at')
            ->get();

        $byArticle = DB::table('stock_movements')
            ->join('expenses', function ($join) {
                $join->on('expenses.id', '=', 'stock_movements.source_id')
                    ->where('stock_movements.source_type', '=', Expense::class);
            })
            ->join('stock_items', 'stock_items.id', '=', 'stock_movements.stock_item_id')
            ->where('stock_movements.reason', 'purchase')
            ->whereBetween('expenses.spent_at', [$start, $end])
            ->groupBy('stock_items.id', 'stock_items.name', 'stock_items.unit')
            ->select(
                'stock_items.name',
                'stock_items.unit',
                DB::raw('SUM(stock_movements.quantity) as qty'),
                DB::raw('SUM(stock_movements.quantity * stock_movements.unit_cost) as value'),
            )
            ->orderByDesc('value')
            ->get();

        return [
            'from' => $from,
            'to' => $to,
            'total' => (float) $expenses->sum('amount'),
            'creditTotal' => (float) $expenses->where('payment_method', PaymentMethod::Credit)->sum('amount'),
            'paidTotal' => (float) $expenses->where('payment_method', '!=', PaymentMethod::Credit)->sum('amount'),
            'byDay' => $expenses->groupBy(fn (Expense $e) => $e->spent_at->toDateString())
                ->map(fn ($group, $day) => [
                    'date' => Carbon::parse($day),
                    'total' => (float) $group->sum('amount'),
                    'count' => $group->count(),
                ])->values(),
            'bySupplier' => $expenses->groupBy(fn (Expense $e) => $e->supplier->name ?? 'Sans compte fournisseur')
                ->map(fn ($group, $name) => [
                    'name' => $name,
                    'total' => (float) $group->sum('amount'),
                    'credit' => (float) $group->where('payment_method', PaymentMethod::Credit)->sum('amount'),
                ])->sortByDesc('total')->values(),
            'byArticle' => $byArticle,
            'list' => $expenses,
        ];
    }

    /**
     * TRÉSORERIE ET FONDS DE ROULEMENT sur un mois :
     *  - ce qui est réellement ENTRÉ (ventes encaissées) ;
     *  - ce qui est réellement SORTI (dépenses payées + factures fournisseurs réglées) ;
     *  - ce qu'on DOIT encore (fournisseurs, salaires du mois) ;
     *  - ce qu'on POSSÈDE en marchandise (valeur du stock).
     */
    public function treasury(string $month): array
    {
        $start = Carbon::parse($month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $sales = Sale::valid()->whereBetween('sold_at', [$start, $end])->get(['total', 'payment_method']);
        $expenses = Expense::whereBetween('spent_at', [$start, $end])->get(['amount', 'payment_method', 'expense_category']);
        $supplierPayments = (float) SupplierPayment::whereBetween('paid_at', [$start, $end])->sum('amount');

        // Dépenses réellement payées : les livraisons à crédit ne sortent pas d'argent ce mois-ci.
        $paidExpenses = (float) $expenses->where('payment_method', '!=', PaymentMethod::Credit)->sum('amount');
        $creditExpenses = (float) $expenses->where('payment_method', PaymentMethod::Credit)->sum('amount');
        $cashIn = (float) $sales->sum('total');
        $cashOut = $paidExpenses + $supplierPayments;

        $supplierDebt = (float) Supplier::all()->sum(fn (Supplier $s) => $s->balance());
        $balance = BalanceSnapshot::orderByDesc('recorded_on')->first();
        $salaryDue = (float) Staff::active()->get()->sum(fn (Staff $s) => max(0, (float) $s->monthly_salary - $s->paidFor($month)));
        $stockValue = (float) StockItem::all()->sum(fn (StockItem $i) => $i->stockValue());

        // Budgets : plafond fixé vs dépensé (les livraisons à crédit comptent : la charge est là).
        $budgets = Budget::where('month', $month)->get()->keyBy(fn (Budget $b) => $b->expense_category->value);
        $spentByCategory = $expenses->groupBy(fn (Expense $e) => $e->expense_category->value)
            ->map(fn ($group) => (float) $group->sum('amount'));

        $categories = collect(ExpenseCategory::options())->map(fn (string $label, string $value) => [
            'value' => $value,
            'label' => $label,
            'budget' => (float) ($budgets[$value]->amount ?? 0),
            'spent' => (float) ($spentByCategory[$value] ?? 0),
        ])->values();

        return [
            'month' => $month,
            'cashIn' => $cashIn,
            'cashOut' => $cashOut,
            'net' => $cashIn - $cashOut,
            'salesByMethod' => $sales->groupBy(fn ($s) => $s->payment_method->value)->map(fn ($g) => (float) $g->sum('total')),
            'paidExpenses' => $paidExpenses,
            'creditExpenses' => $creditExpenses,
            'supplierPayments' => $supplierPayments,
            'supplierDebt' => $supplierDebt,
            'salaryDue' => $salaryDue,
            'stockValue' => $stockValue,
            // Argent réellement disponible, saisi à la main (caisse + comptes mobiles).
            'balance' => $balance,
            'available' => $balance ? $balance->total() : null,
            // POSITION NETTE (fiable) : argent disponible − ce qu'on doit. Sans le stock.
            'netPosition' => $balance ? $balance->total() - $supplierDebt - $salaryDue : null,
            // Fonds de roulement estimé : la même chose en ajoutant la valeur du stock,
            // qui ne vaut que ce que valent les comptages.
            'workingCapital' => ($balance ? $balance->total() : 0) + $stockValue - $supplierDebt - $salaryDue,
            'stockCountedAt' => \App\Models\StockMovement::where('reason', \App\Enums\StockMovementReason::Manual->value)
                ->latest('created_at')->first()?->created_at,
            'keyItemsNeverCounted' => StockItem::key()->get()->filter(fn (StockItem $i) => $i->lastCountedAt() === null)->count(),
            'categories' => $categories,
        ];
    }
}
