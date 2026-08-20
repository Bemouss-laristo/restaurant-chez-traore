<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Expense;
use App\Models\Sale;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ReportService
{
    /** Rapport d'une journée. */
    public function daily(CarbonInterface $date): array
    {
        $salesTotal = (float) Sale::whereDate('sold_at', $date)->sum('total');
        $orders = Sale::whereDate('sold_at', $date)->count();
        $expensesTotal = (float) Expense::whereDate('spent_at', $date)->sum('amount');

        return [
            'salesTotal' => $salesTotal,
            'orders' => $orders,
            'expensesTotal' => $expensesTotal,
            'profit' => $salesTotal - $expensesTotal,
            'byPayment' => Sale::whereDate('sold_at', $date)
                ->selectRaw('payment_method, SUM(total) as total')
                ->groupBy('payment_method')
                ->pluck('total', 'payment_method'),
            'byCategory' => Expense::whereDate('spent_at', $date)
                ->selectRaw('expense_category, SUM(amount) as total')
                ->groupBy('expense_category')
                ->pluck('total', 'expense_category'),
            'topProducts' => $this->topProducts($date, $date),
        ];
    }

    /** Rapport d'une semaine (7 jours à partir du lundi). */
    public function weekly(CarbonInterface $start): array
    {
        $start = $start->copy()->startOfDay();

        $rows = collect(range(0, 6))->map(function (int $i) use ($start) {
            $day = $start->copy()->addDays($i);
            $sales = (float) Sale::whereDate('sold_at', $day)->sum('total');
            $expenses = (float) Expense::whereDate('spent_at', $day)->sum('amount');

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

    /** Rapport d'un mois. */
    public function monthly(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // Ventes agrégées par jour, en une seule requête.
        $salesByDay = Sale::whereBetween('sold_at', [$start, $end])
            ->selectRaw('DATE(sold_at) as d, SUM(total) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $perDay = collect(range(1, $start->daysInMonth))->map(function (int $day) use ($start, $salesByDay) {
            $key = $start->copy()->day($day)->toDateString();

            return [
                'day' => $day,
                'sales' => (float) ($salesByDay[$key] ?? 0),
            ];
        });

        $salesTotal = (float) Sale::whereBetween('sold_at', [$start, $end])->sum('total');
        $expensesTotal = (float) Expense::whereBetween('spent_at', [$start, $end])->sum('amount');

        return [
            'start' => $start,
            'perDay' => $perDay,
            'salesTotal' => $salesTotal,
            'expensesTotal' => $expensesTotal,
            'profit' => $salesTotal - $expensesTotal,
            'byCategory' => Expense::whereBetween('spent_at', [$start, $end])
                ->selectRaw('expense_category, SUM(amount) as total')
                ->groupBy('expense_category')
                ->pluck('total', 'expense_category'),
            'topProducts' => $this->topProducts($start, $end),
        ];
    }

    /** Produits les plus vendus entre deux dates (bornes incluses). */
    private function topProducts(CarbonInterface $from, CarbonInterface $to, int $limit = 5): Collection
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereBetween('sales.sold_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('products.id', 'products.name')
            ->select(
                'products.name',
                DB::raw('SUM(sale_items.quantity) as qty'),
                DB::raw('SUM(sale_items.line_total) as revenue'),
            )
            ->orderByDesc('qty')
            ->limit($limit)
            ->get();
    }
}
