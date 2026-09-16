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
                'items' => [
                    ['label' => 'Pain', 'price' => 200],
                    ['label' => 'Sachets', 'price' => 100],
                    ['label' => '', 'price' => ''], // ligne vide ignorée
                ],
            ])
            ->assertRedirect(route('cashier-expenses.index'));

        $expense = Expense::firstOrFail();
        $this->assertEquals($session->id, $expense->cash_session_id);
        $this->assertEquals($cashier->id, $expense->user_id);
        $this->assertEquals(PaymentMethod::Especes, $expense->payment_method);
        $this->assertEquals(300.0, (float) $expense->amount);
        $this->assertStringContainsString("Pain — 200 MRU", $expense->description);
        $this->assertStringContainsString("Sachets — 100 MRU", $expense->description);
        $this->assertEquals(4700.0, app(CashSessionService::class)->expectedCash($session));

        $this->actingAs($cashier)
            ->get(route('cashier-expenses.index'))
            ->assertOk()
            ->assertSee('Pain — 200 MRU<br', false);
    }

    public function test_an_open_cash_session_is_required(): void
    {
        $cashier = User::factory()->caissier()->create();

        $this->actingAs($cashier)
            ->post(route('cashier-expenses.store'), [
                'expense_category' => 'divers',
                'items' => [['label' => 'Sachets', 'price' => 100]],
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_items_with_a_name_and_positive_price_are_required(): void
    {
        $cashier = User::factory()->caissier()->create();
        CashSession::factory()->create(['user_id' => $cashier->id]);

        $this->actingAs($cashier)
            ->post(route('cashier-expenses.store'), ['expense_category' => 'divers', 'items' => [['label' => '', 'price' => '']]])
            ->assertSessionHasErrors('items');

        $this->actingAs($cashier)
            ->post(route('cashier-expenses.store'), ['expense_category' => 'divers', 'items' => [['label' => 'Pain', 'price' => 0]]])
            ->assertSessionHasErrors('items.0.price');

        $this->assertDatabaseCount('expenses', 0);
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

    public function test_manager_and_admin_see_cashier_expenses_with_who_recorded_them(): void
    {
        $cashier = User::factory()->caissier()->create(['name' => 'Moussa Caisse']);
        CashSession::factory()->create(['user_id' => $cashier->id]);

        $this->actingAs($cashier)->post(route('cashier-expenses.store'), [
            'expense_category' => 'achat_marchandises',
            'items' => [['label' => 'Oignons', 'price' => 150]],
        ]);

        foreach ([User::factory()->gerant()->create(), User::factory()->admin()->create()] as $boss) {
            $this->actingAs($boss)->get(route('expenses.index'))
                ->assertOk()
                ->assertSee('Moussa Caisse')
                ->assertSee('Oignons — 150 MRU', false);

            $this->actingAs($boss)->get(route('expenses.index', ['user_id' => $cashier->id, 'date' => \App\Support\BusinessDay::today()]))
                ->assertOk()
                ->assertSee('Oignons');

            $this->actingAs($boss)->get(route('reports.daily'))
                ->assertOk()
                ->assertSee('Détail des dépenses du jour')
                ->assertSee('Moussa Caisse')
                ->assertSee('Oignons');
        }
    }
}
