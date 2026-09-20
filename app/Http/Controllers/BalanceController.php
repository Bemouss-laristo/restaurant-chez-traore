<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BalanceSnapshot;
use App\Support\BusinessDay;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Relevés hebdomadaires de l'argent disponible (caisse + comptes mobiles). */
class BalanceController extends Controller
{
    public function index(): View
    {
        $snapshots = BalanceSnapshot::with('user')->orderByDesc('recorded_on')->limit(26)->get();

        return view('balances.index', [
            'snapshots' => $snapshots,
            'latest' => $snapshots->first(),
            'previous' => $snapshots->skip(1)->first(),
            'today' => BusinessDay::today(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recorded_on' => ['required', 'date'],
            'cash' => ['required', 'numeric', 'min:0'],
            'bankily' => ['required', 'numeric', 'min:0'],
            'masrivi' => ['required', 'numeric', 'min:0'],
            'sedad' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'cash.required' => 'Indique le montant en caisse (0 si vide).',
        ]);

        // Un seul relevé par jour : le dernier remplace le précédent.
        // (On normalise la date à minuit pour retrouver le relevé existant.)
        $day = Carbon::parse($data['recorded_on'])->startOfDay();

        BalanceSnapshot::updateOrCreate(
            ['recorded_on' => $day],
            ['user_id' => $request->user()->id] + $data + ['recorded_on' => $day],
        );

        return redirect()->route('balances.index')->with('status', 'Relevé enregistré.');
    }
}
