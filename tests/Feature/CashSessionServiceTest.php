<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\CashSession;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\User;
use App\Services\CashSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CashSessionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): CashSessionService
    {
        return app(CashSessionService::class);
    }

    public function test_it_opens_a_session(): void
    {
        $user = User::factory()->create();

        $session = $this->service()->open($user, 1000);

        $this->assertEquals(CashSession::STATUS_OPEN, $session->status);
        $this->assertEquals(1000.0, (float) $session->opening_float);
    }

    public function test_it_prevents_two_open_sessions(): void
    {
        $user = User::factory()->create();
        $this->service()->open($user, 1000);

        $this->expectException(RuntimeException::class);
        $this->service()->open($user, 500);
    }

    public function test_expected_cash_only_counts_cash_payments(): void
    {
        $user = User::factory()->create();
        $session = $this->service()->open($user, 1000);

        Sale::factory()->create(['cash_session_id' => $session->id, 'payment_method' => PaymentMethod::Especes, 'total' => 500]);
        Sale::factory()->create(['cash_session_id' => $session->id, 'payment_method' => PaymentMethod::Bankily, 'total' => 300]);
        Expense::factory()->create(['cash_session_id' => $session->id, 'payment_method' => PaymentMethod::Especes, 'amount' => 200]);

        // 1000 + 500 (espèces) - 200 (espèces) = 1300 ; le 300 Bankily est ignoré.
        $this->assertEquals(1300.0, $this->service()->expectedCash($session->fresh()));
    }

    public function test_close_computes_the_difference(): void
    {
        $user = User::factory()->create();
        $session = $this->service()->open($user, 1000);
        Sale::factory()->create(['cash_session_id' => $session->id, 'payment_method' => PaymentMethod::Especes, 'total' => 500]);

        $closed = $this->service()->close($session->fresh(), 1450);

        // théorique = 1500 ; réel = 1450 ; écart = -50
        $this->assertEquals(CashSession::STATUS_CLOSED, $closed->status);
        $this->assertEquals(1500.0, (float) $closed->expected_cash);
        $this->assertEquals(-50.0, (float) $closed->difference);
    }
}
