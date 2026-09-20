<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CashSession;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\StockItem;
use App\Models\User;
use App\Support\BusinessDay;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class DashboardService
{
    public function __construct(private readonly CashSessionService $cash)
    {
    }

    /** Chiffres clés de la journée commerciale en cours (19h → 5h). */
    public function todayStats(User $user): array
    {
        $date = BusinessDay::today();
        [$start, $end] = BusinessDay::window($date);

        $sales = (float) Sale::valid()->whereBetween('sold_at', [$start, $end])->sum('total');
        $orders = Sale::valid()->whereBetween('sold_at', [$start, $end])->count();
        // Les dépenses sont datées à la main : on les rattache par date commerciale directe.
        $expenses = (float) Expense::whereDate('spent_at', $date)->sum('amount');

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

    /**
     * TOUS les produits vendus depuis le 1er du mois, avec chiffre d'affaires,
     * coût matière et marge : on voit ce qui rapporte, pas seulement ce qui sort.
     */
    public function monthProducts(): Collection
    {
        $today = BusinessDay::today();

        return app(ReportService::class)->productsSold(
            Carbon::parse($today)->startOfMonth()->toDateString(),
            $today,
        );
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

        $envelope = app(EnvelopeService::class)->forMonth(Carbon::parse(BusinessDay::today())->format('Y-m'));
        if ($envelope['aheadOfSchedule']) {
            $alerts[] = [
                'level' => 'warning',
                'message' => sprintf(
                    "Enveloppe du mois consommée à %d %% alors que le mois n'est qu'à %d %%.",
                    (int) $envelope['usedPercent'],
                    (int) $envelope['monthProgress'],
                ),
            ];
        }
        if ($envelope['opening'] > 0 && $envelope['remaining'] < 0) {
            $alerts[] = ['level' => 'danger', 'message' => "Enveloppe du mois épuisée : le reste est négatif."];
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
