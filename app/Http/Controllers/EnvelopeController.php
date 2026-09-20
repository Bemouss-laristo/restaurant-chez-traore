<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\MonthPlan;
use App\Models\ReserveMovement;
use App\Services\CashSessionService;
use App\Services\EnvelopeService;
use App\Support\BusinessDay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Enveloppe du mois : dotation, consommation, surplus et réserve. */
class EnvelopeController extends Controller
{
    public function __construct(
        private readonly EnvelopeService $envelope,
        private readonly CashSessionService $cash,
    ) {
    }

    public function index(Request $request): View
    {
        $month = (string) $request->query('month', Carbon::parse(BusinessDay::today())->format('Y-m'));
        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            $month = Carbon::parse(BusinessDay::today())->format('Y-m');
        }

        return view('envelope.index', [
            'report' => $this->envelope->forMonth($month),
            'monthLabel' => Carbon::parse($month.'-01')->translatedFormat('F Y'),
            'paymentMethods' => PaymentMethod::salesOptions(),
            'today' => BusinessDay::today(),
        ]);
    }

    /** Fixe (ou corrige) la dotation du mois. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'opening_amount' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'opening_amount.required' => "Indique le montant de l'enveloppe du mois.",
        ]);

        MonthPlan::updateOrCreate(
            ['month' => $data['month']],
            $data + ['user_id' => $request->user()->id],
        );

        return back()->with('status', 'Enveloppe du mois enregistrée.');
    }

    /** Met de l'argent de côté (ou en reprend). */
    public function reserve(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'direction' => ['required', 'in:set_aside,take_back'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)->except(PaymentMethod::Credit)],
            'moved_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'amount.required' => 'Indique le montant.',
        ]);

        $user = $request->user();
        $method = PaymentMethod::from($data['payment_method']);
        $signed = $data['direction'] === 'set_aside' ? (float) $data['amount'] : -(float) $data['amount'];

        ReserveMovement::create([
            'user_id' => $user->id,
            'cash_session_id' => $method->affectsCashDrawer() ? $this->cash->currentFor($user)?->id : null,
            'amount' => $signed,
            'payment_method' => $method,
            'moved_on' => Carbon::parse($data['moved_on']),
            'note' => $data['note'] ?? null,
        ]);

        return back()->with('status', $signed > 0
            ? number_format($signed, 0, ',', ' ').' MRU mis de côté.'
            : number_format(abs($signed), 0, ',', ' ').' MRU repris de la réserve.');
    }
}
