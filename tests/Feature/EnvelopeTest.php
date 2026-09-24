<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\CashSession;
use App\Models\MonthPlan;
use App\Models\Product;
use App\Models\ReserveMovement;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CashSessionService;
use App\Services\EnvelopeService;
use App\Services\SaleService;
use App\Support\BusinessDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EnvelopeTest extends TestCase
{
    use RefreshDatabase;

    private function month(): string
    {
        return Carbon::parse(BusinessDay::today())->format('Y-m');
    }

    public function test_sales_refill_the_envelope_and_expenses_empty_it(): void
    {
        $manager = User::factory()->gerant()->create();
        $month = $this->month();

        $this->actingAs($manager)->post(route('envelope.store'), [
            'month' => $month,
            'opening_amount' => 200000,
        ])->assertRedirect();

        // Une vente de 50 000 et un achat payé de 30 000.
        $product = Product::factory()->create(['sale_price' => 50000]);
        app(SaleService::class)->record($manager, [['product_id' => $product->id, 'quantity' => 1]], PaymentMethod::Especes, null);

        $item = StockItem::factory()->create(['quantity' => 0, 'unit_cost' => 0, 'pack_quantity' => null]);
        $this->actingAs($manager)->post(route('purchases.store'), [
            'spent_at' => BusinessDay::today(),
            'payment_method' => 'especes',
            'lines' => [['stock_item_id' => $item->id, 'quantity' => 10, 'total_price' => 30000]],
        ]);

        $report = app(EnvelopeService::class)->forMonth($month);

        $this->assertEquals(200000.0, $report['opening']);
        $this->assertEquals(50000.0, $report['cashIn']);
        $this->assertEquals(30000.0, $report['outflow']);
        // 200 000 + 50 000 − 30 000
        $this->assertEquals(220000.0, $report['remaining']);
        $this->assertEquals(20000.0, $report['surplus']);   // ce qui dépasse la dotation
        $this->assertEquals(20000.0, $report['profit']);    // 50 000 de ventes − 30 000 de charges
        $this->assertEquals(15.0, $report['usedPercent']);  // 30 000 / 200 000
    }

    public function test_setting_money_aside_leaves_the_envelope_and_the_cash_drawer(): void
    {
        $manager = User::factory()->gerant()->create();
        $session = CashSession::factory()->create(['user_id' => $manager->id, 'opening_float' => 100000]);
        $month = $this->month();
        MonthPlan::create(['month' => $month, 'opening_amount' => 200000, 'user_id' => $manager->id]);

        $this->actingAs($manager)->post(route('envelope.reserve'), [
            'direction' => 'set_aside',
            'amount' => 30000,
            'payment_method' => 'especes',
            'moved_on' => BusinessDay::today(),
            'note' => 'Épargne travaux',
        ])->assertRedirect();

        $report = app(EnvelopeService::class)->forMonth($month);
        $this->assertEquals(30000.0, $report['setAside']);
        $this->assertEquals(170000.0, $report['remaining']);
        $this->assertEquals(30000.0, $report['reserveTotal']);

        // L'argent est sorti du tiroir, mais ce n'est pas une dépense.
        $this->assertEquals(70000.0, app(CashSessionService::class)->expectedCash($session));
        $this->assertEquals(0, \App\Models\Expense::count());

        // Reprendre de la réserve fait l'inverse.
        $this->actingAs($manager)->post(route('envelope.reserve'), [
            'direction' => 'take_back',
            'amount' => 10000,
            'payment_method' => 'especes',
            'moved_on' => BusinessDay::today(),
        ]);

        $this->assertEquals(20000.0, (float) ReserveMovement::sum('amount'));
        $this->assertEquals(80000.0, app(CashSessionService::class)->expectedCash($session));
    }

    public function test_the_envelope_warns_when_money_burns_faster_than_time(): void
    {
        // Le scénario est « 90 % dépensés en début de mois » : on fige donc la date
        // au 3, à 21 h (en plein service). Sans cela le test échouait chaque mois
        // à partir du 24, quand le mois écoulé rattrape les 90 % dépensés.
        $this->travelTo(now()->startOfMonth()->addDays(2)->setTime(21, 0));

        $manager = User::factory()->gerant()->create();
        $month = $this->month();
        MonthPlan::create(['month' => $month, 'opening_amount' => 100000, 'user_id' => $manager->id]);

        $supplier = Supplier::create(['name' => 'Boucherie']);
        $item = StockItem::factory()->create(['quantity' => 0, 'unit_cost' => 0, 'pack_quantity' => null]);

        // On dépense 90 % de l'enveloppe dès le début du mois.
        $this->actingAs($manager)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'spent_at' => BusinessDay::today(),
            'payment_method' => 'especes',
            'lines' => [['stock_item_id' => $item->id, 'quantity' => 10, 'total_price' => 90000]],
        ]);

        $report = app(EnvelopeService::class)->forMonth($month);
        $this->assertEquals(90.0, $report['usedPercent']);
        $this->assertTrue($report['aheadOfSchedule']);
        $this->assertGreaterThan(0, $report['burnPerDay']);

        $this->actingAs($manager)->get(route('envelope.index'))
            ->assertOk()
            ->assertSee('Enveloppe du mois')
            ->assertSee('plus vite que le temps');

        // L'alerte remonte aussi sur le tableau de bord.
        $this->actingAs($manager)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Enveloppe du mois consommée');
    }

    public function test_a_cashier_cannot_reach_the_envelope(): void
    {
        $cashier = User::factory()->caissier()->create();

        $this->actingAs($cashier)->get(route('envelope.index'))->assertForbidden();
        $this->actingAs($cashier)->post(route('envelope.reserve'), [
            'direction' => 'set_aside', 'amount' => 100, 'payment_method' => 'especes', 'moved_on' => BusinessDay::today(),
        ])->assertForbidden();
    }
}
