<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Models\Expense;
use App\Models\Staff;
use App\Services\CashSessionService;
use App\Support\BusinessDay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Employés et salaires : qui fait quoi, combien, payé ou non pour le mois. */
class StaffController extends Controller
{
    public function __construct(private readonly CashSessionService $cash)
    {
    }

    public function index(Request $request): View
    {
        $month = (string) $request->query('month', Carbon::parse(BusinessDay::today())->format('Y-m'));
        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            $month = Carbon::parse(BusinessDay::today())->format('Y-m');
        }

        $staff = Staff::orderBy('is_active', 'desc')->orderBy('name')->get();

        $rows = $staff->map(fn (Staff $person) => [
            'person' => $person,
            'paid' => $person->paidFor($month),
            'remaining' => max(0, (float) $person->monthly_salary - $person->paidFor($month)),
        ]);

        return view('staff.index', [
            'rows' => $rows,
            'month' => $month,
            'monthLabel' => Carbon::parse($month.'-01')->translatedFormat('F Y'),
            'payroll' => (float) $staff->where('is_active', true)->sum('monthly_salary'),
            'paidTotal' => (float) $rows->sum('paid'),
            'remainingTotal' => (float) $rows->where('person.is_active', true)->sum('remaining'),
            'paymentMethods' => PaymentMethod::salesOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Staff::create($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'job_title' => ['required', 'string', 'max:80'],
            'monthly_salary' => ['required', 'numeric', 'min:0'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]));

        return back()->with('status', 'Employé ajouté.');
    }

    public function update(Request $request, Staff $staff): RedirectResponse
    {
        $staff->update($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'job_title' => ['required', 'string', 'max:80'],
            'monthly_salary' => ['required', 'numeric', 'min:0'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
        ]) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Employé mis à jour.');
    }

    /** Verse (tout ou partie) du salaire du mois : c'est une dépense « Salaires ». */
    public function pay(Request $request, Staff $staff): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)->except(PaymentMethod::Credit)],
            'month' => ['required', 'date_format:Y-m'],
        ], [
            'amount.required' => 'Indique le montant versé.',
        ]);

        $user = $request->user();
        $method = PaymentMethod::from($data['payment_method']);

        Expense::create([
            'user_id' => $user->id,
            'cash_session_id' => $method->affectsCashDrawer() ? $this->cash->currentFor($user)?->id : null,
            'staff_id' => $staff->id,
            'salary_month' => $data['month'],
            'expense_category' => ExpenseCategory::Salaires,
            'amount' => $data['amount'],
            'description' => 'Salaire '.$data['month'].' — '.$staff->name.' ('.$staff->job_title.')',
            'payment_method' => $method,
            'spent_at' => Carbon::parse(BusinessDay::today()),
        ]);

        return back()->with('status', 'Salaire enregistré pour '.$staff->name.'.');
    }
}
