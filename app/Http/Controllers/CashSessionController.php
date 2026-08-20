<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CloseCashSessionRequest;
use App\Http\Requests\OpenCashSessionRequest;
use App\Models\CashSession;
use App\Services\CashSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class CashSessionController extends Controller
{
    public function __construct(private readonly CashSessionService $service)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $current = $this->service->currentFor($user);

        return view('caisse.index', [
            'current' => $current,
            'cashSales' => $current ? $this->service->cashSales($current) : 0.0,
            'cashExpenses' => $current ? $this->service->cashExpenses($current) : 0.0,
            'expected' => $current ? $this->service->expectedCash($current) : 0.0,
            'history' => CashSession::with('user')
                ->where('status', CashSession::STATUS_CLOSED)
                ->latest('closed_at')
                ->paginate(10),
        ]);
    }

    public function open(OpenCashSessionRequest $request): RedirectResponse
    {
        try {
            $this->service->open($request->user(), (float) $request->validated()['opening_float']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Caisse ouverte.');
    }

    public function close(CloseCashSessionRequest $request): RedirectResponse
    {
        $current = $this->service->currentFor($request->user());

        if ($current === null) {
            return back()->with('error', 'Aucune caisse ouverte à clôturer.');
        }

        $this->service->close($current, (float) $request->validated()['counted_cash']);

        return back()->with('status', 'Caisse clôturée.');
    }
}
