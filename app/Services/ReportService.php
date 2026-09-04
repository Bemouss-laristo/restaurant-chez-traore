<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Expense;
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
 *  - les DÉPENSES sont datées à la main, donc rattachées par leur date directe.
 */
final class ReportService
{
    /** Rapport d'une journée commerciale. */
    public function daily(CarbonInterface $date): array
    {
        $d = $date->toDateString();
        [$start, $end] = BusinessDay::window($d);

        $salesTotal = (float) Sale::whereBetween('sold_at', [$start, $end])->sum('total');
        $orders = Sale::whereBetween('sold_at', [$start, $end])->count();
        $expensesTotal = (float) Expense::whereDate('spent_at', $d)->sum('amount');

        return [
            'salesTotal' => $salesTotal,
            'orders' => $orders,
            'expensesTotal' => $expensesTotal,
            'profit' => $salesTotal - $expensesTotal,
            'byPayment' => Sale::whereBetween('sold_at', [$start, $end])
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

            $sales = (float) Sale::whereBetween('sold_at', [$ws, $we])->sum('total');
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
        $salesByDay = Sale::whereBetween('sold_at', [$rangeStart, $rangeEnd])
            ->selectRaw("DATE(sold_at - INTERVAL {$hour} HOUR) as d, SUM(total) as total")
            ->groupBy('d')
            ->pluck('total', 'd');

        $perDay = collect(range(1, $first->daysInMonth))->map(function (int $day) use ($first, $salesByDay) {
            $key = $first->copy()->day($day)->toDateString();

            return ['day' => $day, 'sales' => (float) ($salesByDay[$key] ?? 0)];
        });

        $monthStart = $first->copy()->startOfDay();
        $monthEnd = $last->copy()->endOfDay();
        $salesTotal = (float) Sale::whereBetween('sold_at', [$rangeStart, $rangeEnd])->sum('total');
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
                ->whereBetween('created_at', [$start, $end])
                ->sum('quantity');
            $out = (float) $item->movements()
                ->where('type', 'out')
                ->whereBetween('created_at', [$start, $end])
                ->sum('quantity');

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
}
