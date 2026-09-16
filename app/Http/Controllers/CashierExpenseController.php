<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Http\Requests\StoreCashierExpenseRequest;
use App\Models\Expense;
use App\Services\CashSessionService;
use App\Support\BusinessDay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * « Cahier de dépenses » du caissier : petites dépenses payées en espèces
 * depuis le tiroir-caisse (pain, sachets, transport…).
 *
 * Le caissier peut AJOUTER et VOIR ses dépenses du jour, mais pas les modifier
 * ni les supprimer : seuls le gérant et l'admin le peuvent (menu Dépenses).
 */
class CashierExpenseController extends Controller
{
    public function __construct(private readonly CashSessionService $cash)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $session = $this->cash->currentFor($user);

        $expenses = Expense::where('user_id', $user->id)
            ->whereDate('spent_at', BusinessDay::today())
            ->latest('created_at')
            ->get();

        return view('cashier-expenses.index', [
            'session' => $session,
            'expenses' => $expenses,
            'total' => (float) $expenses->sum('amount'),
            'expected' => $session ? $this->cash->expectedCash($session) : null,
            'categories' => ExpenseCategory::options(),
        ]);
    }

    public function store(StoreCashierExpenseRequest $request): RedirectResponse
    {
        $user = $request->user();
        $session = $this->cash->currentFor($user);

        // L'argent sort du tiroir : sans caisse ouverte, la dépense ne serait
        // rattachée à rien et fausserait le comptage.
        if ($session === null) {
            return back()
                ->withInput()
                ->with('error', "Ouvre d'abord la caisse avant de noter une dépense.");
        }

        $data = $request->validated();

        // Le total est calculé côté serveur à partir des lignes (jamais confié au navigateur).
        $items = collect($data['items']);
        $amount = round((float) $items->sum(fn ($row) => (float) $row['price']), 2);
        $description = $items
            ->map(fn ($row) => '• '.$row['label'].' — '.number_format((float) $row['price'], 0, ',', ' ').' MRU')
            ->implode("\n");

        Expense::create([
            'user_id' => $user->id,
            'cash_session_id' => $session->id,
            'expense_category' => $data['expense_category'],
            'amount' => $amount,
            'description' => $description,
            'payment_method' => PaymentMethod::Especes,
            // Datée sur la journée commerciale (une dépense à 1h compte pour la soirée),
            // l'heure réelle reste disponible dans created_at.
            'spent_at' => Carbon::parse(BusinessDay::today())->setTimeFrom(now()),
        ]);

        return redirect()
            ->route('cashier-expenses.index')
            ->with('status', 'Dépense de '.number_format($amount, 0, ',', ' ').' MRU enregistrée et retirée de la caisse.');
    }
}
