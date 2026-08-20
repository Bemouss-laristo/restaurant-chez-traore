<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CashSession;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\StockItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class DashboardService
{
    public function __construct(private readonly CashSessionService $cash)
    {
    }

    /** Chiffres clés du jour pour l'utilisateur connecté. */
    public function todayStats(User $user): array
    {
        $today = today();

        $sales = (float) Sale::whereDate('sold_at', $today)->sum('total');
        $expenses = (float) Expense::whereDate('spent_at', $today)->sum('amount');
        $orders = Sale::whereDate('sold_at', $today)->count();

        $session = $this->cash->currentFor($user);

        return [
            'sales' => $sales,
            'expenses' => $expenses,
            'profit' => $sales - $expenses,
            'orders' => $orders,
            'cashBalance' => $session !== null ? $this->cash->expectedCash($session) : null,
            'hasOpenSession' => $session !== null,
        ];
    }

    /** Produits les plus vendus sur les N derniers jours. */
    public function topProducts(int $days = 30, int $limit = 5): Collection
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.sold_at', '>=', now()->subDays($days))
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

    public function lowStockItems(): Collection
    {
        return StockItem::lowStock()->orderBy('name')->get();
    }

    /** Alertes automatiques à afficher en tête du tableau de bord. */
    public function alerts(array $today): array
    {
        $alerts = [];

        $lowCount = StockItem::lowStock()->count();
        if ($lowCount > 0) {
            $alerts[] = ['level' => 'warning', 'message' => "{$lowCount} article(s) en stock faible."];
        }

        if ($today['sales'] > 0 && $today['expenses'] > $today['sales']) {
            $alerts[] = ['level' => 'danger', 'message' => "Les dépenses du jour dépassent les ventes."];
        }

        if ($today['profit'] < 0) {
            $alerts[] = ['level' => 'danger', 'message' => 'Bénéfice du jour négatif.'];
        }

        $lastClosed = CashSession::where('status', CashSession::STATUS_CLOSED)
            ->latest('closed_at')
            ->first();
        if ($lastClosed !== null && (float) $lastClosed->difference !== 0.0) {
            $ecart = number_format((float) $lastClosed->difference, 0, ',', ' ');
            $alerts[] = ['level' => 'warning', 'message' => "Écart sur la dernière caisse clôturée : {$ecart} MRU."];
        }

        return $alerts;
    }
}
