<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\StockMovementReason;
use App\Models\CashSession;
use App\Models\Product;
use App\Models\StockItem;
use App\Models\User;
use App\Services\CashSessionService;
use App\Services\DashboardService;
use App\Services\OrderService;
use App\Services\ReportService;
use App\Services\SaleService;
use App\Support\BusinessDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SaleCancellationTest extends TestCase
{
    use RefreshDatabase;

    private function sellOne(User $cashier, ?CashSession $session, float $price = 500, ?StockItem $ingredient = null): \App\Models\Sale
    {
        $product = Product::factory()->create(['sale_price' => $price]);
        if ($ingredient) {
            $product->stockItems()->attach($ingredient->id, ['quantity_needed' => 1]);
        }

        return app(SaleService::class)->record(
            $cashier,
            [['product_id' => $product->id, 'quantity' => 2]],
            PaymentMethod::Especes,
            $session,
        );
    }

    public function test_cashier_cancels_a_sale_with_a_reason(): void
    {
        $cashier = User::factory()->caissier()->create();
        $session = CashSession::factory()->create(['user_id' => $cashier->id, 'opening_float' => 1000]);
        $ingredient = StockItem::factory()->create(['quantity' => 10]);
        $sale = $this->sellOne($cashier, $session, 500, $ingredient);

        $cash = app(CashSessionService::class);
        $this->assertEquals(2000.0, $cash->expectedCash($session));
        $this->assertEquals(8.0, (float) $ingredient->fresh()->quantity);

        $this->actingAs($cashier)
            ->post(route('sales.cancel', $sale), ['cancellation_reason' => 'Le client a annulé'])
            ->assertRedirect(route('sales.show', $sale));

        $sale->refresh();
        $this->assertTrue($sale->isCancelled());
        $this->assertEquals($cashier->id, $sale->cancelled_by);
        $this->assertEquals('Le client a annulé', $sale->cancellation_reason);

        // Caisse revenue au fond de caisse, stock remis.
        $this->assertEquals(1000.0, $cash->expectedCash($session));
        $this->assertEquals(10.0, (float) $ingredient->fresh()->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $ingredient->id,
            'reason' => StockMovementReason::SaleCancelled->value,
        ]);
    }

    public function test_a_reason_is_required(): void
    {
        $cashier = User::factory()->caissier()->create();
        $sale = $this->sellOne($cashier, null);

        $this->actingAs($cashier)
            ->post(route('sales.cancel', $sale), ['cancellation_reason' => ''])
            ->assertSessionHasErrors('cancellation_reason');

        $this->assertFalse($sale->fresh()->isCancelled());
    }

    public function test_a_sale_cannot_be_cancelled_twice(): void
    {
        $manager = User::factory()->gerant()->create();
        $ingredient = StockItem::factory()->create(['quantity' => 10]);
        $sale = $this->sellOne($manager, null, 500, $ingredient);

        $this->actingAs($manager)->post(route('sales.cancel', $sale), ['cancellation_reason' => 'Erreur']);
        $this->actingAs($manager)
            ->post(route('sales.cancel', $sale), ['cancellation_reason' => 'Encore'])
            ->assertSessionHas('error');

        // Stock remis une seule fois.
        $this->assertEquals(10.0, (float) $ingredient->fresh()->quantity);
    }

    public function test_cashier_cannot_cancel_a_sale_from_a_closed_cash_session(): void
    {
        $cashier = User::factory()->caissier()->create();
        $session = CashSession::factory()->closed()->create(['user_id' => $cashier->id]);
        $sale = $this->sellOne($cashier, $session);

        $this->actingAs($cashier)
            ->post(route('sales.cancel', $sale), ['cancellation_reason' => 'Trop tard'])
            ->assertSessionHas('error');

        $this->assertFalse($sale->fresh()->isCancelled());
    }

    public function test_cashier_cannot_cancel_a_sale_from_a_previous_day(): void
    {
        $cashier = User::factory()->caissier()->create();
        $sale = $this->sellOne($cashier, null);
        $sale->update(['sold_at' => Carbon::now()->subDays(3)]);

        $this->actingAs($cashier)
            ->post(route('sales.cancel', $sale), ['cancellation_reason' => 'Vieux'])
            ->assertSessionHas('error');

        $this->assertFalse($sale->fresh()->isCancelled());
    }

    public function test_manager_can_cancel_a_sale_from_a_closed_session(): void
    {
        $cashier = User::factory()->caissier()->create();
        $manager = User::factory()->gerant()->create();
        $session = CashSession::factory()->closed()->create(['user_id' => $cashier->id]);
        $sale = $this->sellOne($cashier, $session);

        $this->actingAs($manager)
            ->post(route('sales.cancel', $sale), ['cancellation_reason' => 'Correction gérant'])
            ->assertRedirect();

        $this->assertTrue($sale->fresh()->isCancelled());
    }

    public function test_cancelled_sales_are_excluded_from_reports_and_dashboard(): void
    {
        $cashier = User::factory()->caissier()->create();
        $kept = $this->sellOne($cashier, null, 300);      // 600
        $cancelled = $this->sellOne($cashier, null, 1000); // 2000

        app(SaleService::class)->cancel($cancelled, $cashier, 'Client parti');

        $daily = app(ReportService::class)->daily(Carbon::parse(BusinessDay::today()));
        $this->assertEquals(600.0, $daily['salesTotal']);
        $this->assertEquals(1, $daily['orders']);
        $this->assertEquals(2, (int) $daily['productsSold']->sum('qty'));

        $this->assertEquals(1, $daily['cancelled']['count']);
        $this->assertEquals(2000.0, $daily['cancelled']['total']);

        $manager = User::factory()->gerant()->create();
        $this->actingAs($manager)->get(route('reports.daily'))
            ->assertOk()
            ->assertSee('1 annulée(s)')
            ->assertSee('Client parti')
            ->assertSee($cancelled->sale_number);
        $this->actingAs($manager)->get(route('reports.weekly'))->assertOk()->assertSee('Client parti');

        $stats = app(DashboardService::class)->todayStats($cashier);
        $this->assertEquals(600.0, $stats['sales']);
        $this->assertEquals(1, $stats['orders']);
    }

    public function test_cancelling_the_sale_of_an_online_order_cancels_the_order(): void
    {
        $cashier = User::factory()->caissier()->create();
        $product = Product::factory()->create(['sale_price' => 200]);
        $order = app(OrderService::class)->createFromCart(
            [['product_id' => $product->id, 'quantity' => 1]], 'Client', '49625325', null,
        );

        $this->actingAs($cashier)->post(route('orders.checkout', $order), ['payment_method' => 'especes']);
        $order->refresh();
        $this->assertEquals(OrderStatus::Terminee, $order->status);

        $this->actingAs($cashier)
            ->post(route('sales.cancel', $order->sale_id), ['cancellation_reason' => 'Client ne vient pas']);

        $this->assertEquals(OrderStatus::Annulee, $order->fresh()->status);
        $this->actingAs($cashier)->get(route('orders.index'))->assertOk()->assertSee('Voir la vente');

        $manager = User::factory()->gerant()->create();
        $this->actingAs($manager)->get(route('reports.daily'))->assertOk();
        $this->actingAs($manager)->get(route('reconciliation.index'))->assertOk();
        $this->actingAs($manager)->get(route('dashboard'))->assertOk();
    }

    public function test_the_receipt_prints_a_client_and_a_kitchen_copy(): void
    {
        $cashier = User::factory()->caissier()->create();
        $sale = $this->sellOne($cashier, null);

        $this->actingAs($cashier)
            ->get(route('sales.receipt', $sale))
            ->assertOk()
            ->assertSeeInOrder(['*** CLIENT ***', $sale->sale_number, '*** CUISINE ***', $sale->sale_number]);
    }

    public function test_sale_page_shows_the_cancel_form_to_the_cashier(): void
    {
        $cashier = User::factory()->caissier()->create();
        $sale = $this->sellOne($cashier, null);

        $this->actingAs($cashier)->get(route('sales.show', $sale))
            ->assertOk()
            ->assertSee('Annuler la vente')
            ->assertSee('printTicket(', false)
            ->assertDontSee('target="_blank"', false);
        $this->actingAs($cashier)->get(route('sales.index'))->assertOk()->assertSee('Voir / Annuler');
    }
}
