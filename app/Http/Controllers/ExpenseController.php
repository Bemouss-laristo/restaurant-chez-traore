<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\User;
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
        $userId = $request->integer('user_id');
        $date = (string) $request->query('date', '');
        if ($date !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = '';
        }

        // Toutes les dépenses, y compris celles notées par les caissiers depuis la caisse.
        $query = Expense::query()
            ->with('user')
            ->when($category !== '', fn ($q) => $q->where('expense_category', $category))
            ->when($userId > 0, fn ($q) => $q->where('user_id', $userId))
            ->when($date !== '', fn ($q) => $q->whereDate('spent_at', $date))
            ->latest('spent_at')
            ->latest('id');

        return view('expenses.index', [
            'expenses' => $query->paginate(20)->withQueryString(),
            'categories' => ExpenseCategory::options(),
            'category' => $category,
            'employees' => User::whereIn('id', Expense::select('user_id')->distinct())->orderBy('name')->get(),
            'userId' => $userId,
            'date' => $date,
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
