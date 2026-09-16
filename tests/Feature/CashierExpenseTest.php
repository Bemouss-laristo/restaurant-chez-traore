<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\CashSession;
use App\Models\Expense;
use App\Models\User;
use App\Services\CashSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_records_an_expense_taken_from_the_cash_drawer(): void
    {
        $cashier = User::factory()->caissier()->create();
        $session = CashSession::factory()->create(['user_id' => $cashier->id, 'opening_float' => 5000]);

        $this->actingAs($cashier)
            ->post(route('cashier-expenses.store'), [
                'expense_category' => 'achat_marchandises',
                'amount' => 300,
                'description' => 'Pain',
            ])
            ->assertRedirect(route('cashier-expenses.index'));

        $expense = Expense::firstOrFail();
        $this->assertEquals($session->id, $expense->cash_session_id);
        $this->assertEquals($cashier->id, $expense->user_id);
        $this->assertEquals(PaymentMethod::Especes, $expense->payment_method);
        $this->assertEquals(4700.0, app(CashSessionService::class)->expectedCash($session));

        $this->actingAs($cashier)
            ->get(route('cashier-expenses.index'))
            ->assertOk()
            ->assertSee('Pain');
    }

    public function test_an_open_cash_session_is_required(): void
    {
        $cashier = User::factory()->caissier()->create();

        $this->actingAs($cashier)
            ->post(route('cashier-expenses.store'), [
                'expense_category' => 'divers',
                'amount' => 100,
                'description' => 'Sachets',
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_description_and_positive_amount_are_required(): void
    {
        $cashier = User::factory()->caissier()->create();
        CashSession::factory()->create(['user_id' => $cashier->id]);

        $this->actingAs($cashier)
            ->post(route('cashier-expenses.store'), ['expense_category' => 'divers', 'amount' => 0, 'description' => ''])
            ->assertSessionHasErrors(['amount', 'description']);
    }

    public function test_cashier_only_sees_own_expenses_and_cannot_edit_them(): void
    {
        $cashier = User::factory()->caissier()->create();
        $other = User::factory()->caissier()->create();
        $expense = Expense::factory()->create(['user_id' => $other->id, 'description' => 'Dépense d un autre', 'spent_at' => now()]);

        $this->actingAs($cashier)->get(route('cashier-expenses.index'))->assertOk()->assertDontSee('Dépense d un autre');

        // Les écrans de gestion des dépenses restent réservés au gérant/admin.
        $this->actingAs($cashier)->get(route('expenses.edit', $expense))->assertForbidden();
        $this->actingAs($cashier)->delete(route('expenses.destroy', $expense))->assertForbidden();
    }

    public function test_cashier_sees_the_expenses_tab(): void
    {
        $cashier = User::factory()->caissier()->create();

        $this->actingAs($cashier)->get('/caisse')->assertOk()->assertSee(route('cashier-expenses.index'));
    }
}
