<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\Expense;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\CashSessionService;
use App\Support\BusinessDay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Comptes fournisseurs : ce qu'on a pris à crédit, ce qu'on a payé, ce qu'on doit.
 */
class SupplierController extends Controller
{
    public function __construct(private readonly CashSessionService $cash)
    {
    }

    public function index(): View
    {
        $suppliers = Supplier::with('stockItems')->orderBy('name')->get();

        return view('suppliers.index', [
            'suppliers' => $suppliers,
            'totalDue' => $suppliers->sum(fn (Supplier $s) => $s->balance()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Supplier::create($request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:suppliers,name'],
            'phone' => ['nullable', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:255'],
        ]));

        return back()->with('status', 'Fournisseur ajouté.');
    }

    /** Modifier le nom, le téléphone ou la note d'un fournisseur. */
    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('suppliers', 'name')->ignore($supplier->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ] , [
            'name.unique' => 'Un fournisseur porte déjà ce nom.',
        ]) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Fournisseur mis à jour.');
    }

    /**
     * Suppression : refusée dès qu'il y a un historique.
     *
     * Effacer un fournisseur qui a des livraisons effacerait aussi la trace de ce
     * qu'on lui a pris et payé — l'historique deviendrait faux. Dans ce cas on le
     * désactive : il disparaît des listes de saisie, mais ses relevés restent.
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        $deliveries = $supplier->deliveries()->count();
        $payments = $supplier->payments()->count();

        if ($deliveries > 0 || $payments > 0) {
            return back()->with('error', sprintf(
                '%s a %d livraison(s) et %d règlement(s) enregistrés : le supprimer effacerait cet historique. Désactive-le plutôt, il disparaîtra des listes de saisie.',
                $supplier->name,
                $deliveries,
                $payments,
            ));
        }

        // Les articles qui le désignaient ne pointent plus vers personne.
        $supplier->stockItems()->update(['supplier_id' => null]);
        $supplier->delete();

        return redirect()->route('suppliers.index')->with('status', 'Fournisseur supprimé.');
    }

    /** Relevé du mois : livraisons, règlements et solde dû. */
    public function show(Request $request, Supplier $supplier): View
    {
        $month = (string) $request->query('month', Carbon::parse(BusinessDay::today())->format('Y-m'));
        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            $month = Carbon::parse(BusinessDay::today())->format('Y-m');
        }

        $start = Carbon::parse($month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $deliveries = Expense::where('supplier_id', $supplier->id)
            ->whereBetween('spent_at', [$start, $end])
            ->with(['user', 'stockMovements.stockItem'])
            ->orderBy('spent_at')
            ->get();

        // Le détail qui compte vraiment : combien a-t-on pris, chaque jour, de quoi.
        // C'est ce tableau qu'on compare ligne à ligne à la facture du fournisseur.
        $takings = $deliveries
            ->flatMap(fn (Expense $expense) => $expense->stockMovements->map(fn ($movement) => [
                'date' => $expense->spent_at,
                'item' => $movement->stockItem?->name ?? 'Article supprimé',
                'unit' => $movement->stockItem?->unit->value ?? '',
                'quantity' => (float) $movement->quantity,
                'unitPrice' => (float) ($movement->unit_cost ?? 0),
                'amount' => (float) $movement->quantity * (float) ($movement->unit_cost ?? 0),
                'credit' => $expense->isCredit(),
                'user' => $expense->user->name ?? '—',
            ]))
            ->sortBy('date')
            ->values();

        $byItem = $takings
            ->groupBy('item')
            ->map(fn ($rows, $name) => [
                'name' => $name,
                'unit' => $rows->first()['unit'],
                'quantity' => round($rows->sum('quantity'), 3),
                'amount' => round($rows->sum('amount'), 2),
                'days' => $rows->pluck('date')->map(fn ($d) => $d->toDateString())->unique()->count(),
            ])
            ->sortBy('name')
            ->values();

        return view('suppliers.show', [
            'supplier' => $supplier,
            'month' => $month,
            'monthLabel' => $start->translatedFormat('F Y'),
            'deliveries' => $deliveries,
            'takings' => $takings,
            'byItem' => $byItem,
            'payments' => SupplierPayment::where('supplier_id', $supplier->id)
                ->whereBetween('paid_at', [$start, $end])
                ->with('user')
                ->orderBy('paid_at')
                ->get(),
            'monthCredit' => $supplier->creditTotal($start->toDateString(), $end->toDateString()),
            'monthPaid' => $supplier->paidTotal($start->toDateString(), $end->toDateString()),
            'balance' => $supplier->balance(),
            'items' => $supplier->stockItems()->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::salesOptions(),
        ]);
    }

    /**
     * Annuler un règlement saisi par erreur (mauvais montant, essai de mise en route).
     * L'argent revient en caisse et la dette remonte d'autant : sans cela, un montant
     * faux resterait dans le solde du fournisseur pour toujours.
     */
    public function deletePayment(Supplier $supplier, SupplierPayment $payment): RedirectResponse
    {
        if ($payment->supplier_id !== $supplier->id) {
            return back()->with('error', 'Ce règlement ne concerne pas ce fournisseur.');
        }

        $amount = number_format((float) $payment->amount, 0, ',', ' ');
        $payment->delete();

        return back()->with('status', 'Règlement de '.$amount.' MRU supprimé. Le solde de '.$supplier->name.' a été remis comme avant.');
    }

    /** Règlement d'une facture : de l'argent sort, la dette baisse. */
    public function pay(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)->except(PaymentMethod::Credit)],
            'paid_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'amount.required' => 'Indique le montant payé.',
        ]);

        $user = $request->user();
        $method = PaymentMethod::from($data['payment_method']);

        SupplierPayment::create([
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'cash_session_id' => $method->affectsCashDrawer() ? $this->cash->currentFor($user)?->id : null,
            'amount' => $data['amount'],
            'payment_method' => $method,
            'paid_at' => Carbon::parse($data['paid_at']),
            'note' => $data['note'] ?? null,
        ]);

        return back()->with('status', 'Règlement enregistré : '.number_format((float) $data['amount'], 0, ',', ' ').' MRU. Reste dû : '.number_format($supplier->fresh()->balance(), 0, ',', ' ').' MRU.');
    }
}
