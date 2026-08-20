<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Services\CashSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(private readonly CashSessionService $cash)
    {
    }

    public function index(Request $request): View
    {
        $category = (string) $request->query('category', '');

        $query = Expense::query()
            ->with('user')
            ->when($category !== '', fn ($q) => $q->where('expense_category', $category))
            ->latest('spent_at');

        return view('expenses.index', [
            'expenses' => $query->paginate(20)->withQueryString(),
            'categories' => ExpenseCategory::options(),
            'category' => $category,
            'total' => (clone $query)->sum('amount'),
        ]);
    }

    public function create(): View
    {
        return view('expenses.create', [
            'categories' => ExpenseCategory::options(),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $data['user_id'] = $user->id;

        // Une dépense en espèces sort du tiroir : on la rattache à la caisse ouverte.
        if (PaymentMethod::from($data['payment_method'])->affectsCashDrawer()) {
            $data['cash_session_id'] = $this->cash->currentFor($user)?->id;
        }

        Expense::create($data);

        return redirect()->route('expenses.index')->with('status', 'Dépense enregistrée.');
    }

    public function edit(Expense $expense): View
    {
        return view('expenses.edit', [
            'expense' => $expense,
            'categories' => ExpenseCategory::options(),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $expense->update($request->validated());

        return redirect()->route('expenses.index')->with('status', 'Dépense mise à jour.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return back()->with('status', 'Dépense supprimée.');
    }
}
