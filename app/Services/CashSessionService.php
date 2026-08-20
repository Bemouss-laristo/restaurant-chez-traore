<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\CashSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Gestion des sessions de caisse.
 *
 * Rappel de la logique financière : seul l'argent liquide (espèces) transite
 * par le tiroir-caisse. Bankily / Sedad / Masrivi n'entrent pas dans l'écart.
 */
final class CashSessionService
{
    /** La session ouverte de cet utilisateur, s'il y en a une. */
    public function currentFor(User $user): ?CashSession
    {
        return CashSession::open()
            ->where('user_id', $user->id)
            ->latest('opened_at')
            ->first();
    }

    public function open(User $user, float $openingFloat): CashSession
    {
        if ($this->currentFor($user) !== null) {
            throw new RuntimeException('Une caisse est déjà ouverte. Clôture-la avant d\'en ouvrir une nouvelle.');
        }

        return CashSession::create([
            'user_id' => $user->id,
            'opened_at' => now(),
            'opening_float' => $openingFloat,
            'status' => CashSession::STATUS_OPEN,
        ]);
    }

    /** Total des ventes en espèces rattachées à la session. */
    public function cashSales(CashSession $session): float
    {
        return (float) $session->sales()
            ->where('payment_method', PaymentMethod::Especes->value)
            ->sum('total');
    }

    /** Total des dépenses en espèces rattachées à la session. */
    public function cashExpenses(CashSession $session): float
    {
        return (float) $session->expenses()
            ->where('payment_method', PaymentMethod::Especes->value)
            ->sum('amount');
    }

    /** Caisse théorique = fond + ventes espèces − dépenses espèces. */
    public function expectedCash(CashSession $session): float
    {
        return (float) $session->opening_float
            + $this->cashSales($session)
            - $this->cashExpenses($session);
    }

    public function close(CashSession $session, float $countedCash): CashSession
    {
        return DB::transaction(function () use ($session, $countedCash) {
            $session = CashSession::whereKey($session->getKey())->lockForUpdate()->firstOrFail();

            $expected = $this->expectedCash($session);

            $session->update([
                'closed_at' => now(),
                'expected_cash' => $expected,
                'counted_cash' => $countedCash,
                'difference' => $countedCash - $expected,
                'status' => CashSession::STATUS_CLOSED,
            ]);

            return $session;
        });
    }
}
