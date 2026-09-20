<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Expense;
use App\Models\MonthPlan;
use App\Models\ReserveMovement;
use App\Models\Sale;
use App\Models\SupplierPayment;
use Illuminate\Support\Carbon;

/**
 * L'ENVELOPPE DU MOIS : l'argent de travail mis en route au début du mois.
 *
 *   Reste = dotation + encaissements − sorties − mises de côté
 *
 * Les ventes reconstituent l'enveloppe ; ce qui dépasse la dotation est du
 * surplus, donc mobilisable sans fragiliser le mois suivant.
 *
 * L'indicateur le plus utile n'est pas le reste, mais la VITESSE : si 60 % de
 * l'enveloppe est consommée alors que le mois n'est qu'à 33 %, le problème est
 * visible le 10, pas le 28.
 */
final class EnvelopeService
{
    public function forMonth(string $month): array
    {
        $start = Carbon::parse($month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $today = Carbon::now();

        $plan = MonthPlan::where('month', $month)->first();
        $opening = (float) ($plan->opening_amount ?? 0);

        $sales = Sale::valid()->whereBetween('sold_at', [$start, $end]);
        $cashIn = (float) $sales->sum('total');

        $expenses = Expense::whereBetween('spent_at', [$start, $end])->get(['amount', 'payment_method']);
        $paidExpenses = (float) $expenses->where('payment_method', '!=', PaymentMethod::Credit)->sum('amount');
        $creditExpenses = (float) $expenses->where('payment_method', PaymentMethod::Credit)->sum('amount');
        $supplierPayments = (float) SupplierPayment::whereBetween('paid_at', [$start, $end])->sum('amount');

        $outflow = $paidExpenses + $supplierPayments;
        $setAside = (float) ReserveMovement::whereBetween('moved_on', [$start, $end])->sum('amount');

        $remaining = $opening + $cashIn - $outflow - $setAside;

        // Avancement du mois et vitesse de consommation.
        $daysInMonth = (int) $start->daysInMonth;
        $daysElapsed = $today->between($start, $end)
            ? (int) $start->diffInDays($today) + 1
            : ($today->greaterThan($end) ? $daysInMonth : 0);
        $monthProgress = $daysInMonth > 0 ? min(100, $daysElapsed / $daysInMonth * 100) : 0.0;
        $usedPercent = $opening > 0 ? min(999, $outflow / $opening * 100) : 0.0;
        $burnPerDay = $daysElapsed > 0 ? $outflow / $daysElapsed : 0.0;
        $projection = $burnPerDay * $daysInMonth;
        $daysCovered = $burnPerDay > 0 ? (int) floor(max(0, $remaining) / $burnPerDay) : null;

        return [
            'month' => $month,
            'plan' => $plan,
            'opening' => $opening,
            'cashIn' => $cashIn,
            'outflow' => $outflow,
            'paidExpenses' => $paidExpenses,
            'creditExpenses' => $creditExpenses,
            'supplierPayments' => $supplierPayments,
            'setAside' => $setAside,
            'remaining' => $remaining,
            // Ce qui dépasse la dotation : mobilisable sans toucher à l'argent de travail.
            'surplus' => max(0, $remaining - $opening),
            'monthProgress' => $monthProgress,
            'usedPercent' => $usedPercent,
            'burnPerDay' => $burnPerDay,
            'projection' => $projection,
            'daysElapsed' => $daysElapsed,
            'daysInMonth' => $daysInMonth,
            'daysCovered' => $daysCovered,
            'runsOutOn' => $daysCovered !== null ? Carbon::now()->addDays($daysCovered) : null,
            // Résultat économique du mois : les achats à crédit comptent, même non payés.
            'profit' => $cashIn - ($paidExpenses + $creditExpenses),
            'reserveTotal' => (float) ReserveMovement::sum('amount'),
            'movements' => ReserveMovement::with('user')
                ->whereBetween('moved_on', [$start, $end])
                ->orderByDesc('moved_on')
                ->get(),
            // Alerte : on brûle l'enveloppe plus vite que le temps ne passe.
            'aheadOfSchedule' => $opening > 0 && $usedPercent > $monthProgress + 10,
        ];
    }
}
