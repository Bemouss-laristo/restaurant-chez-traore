<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BalanceSnapshot;
use App\Models\Staff;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ReportService;
use App\Services\StockService;
use App\Support\BusinessDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_key_stock_items_command_creates_them_with_their_packaging(): void
    {
        $this->artisan('restaurant:articles-cles')->assertSuccessful();

        $pain = StockItem::where('name', 'Pain arabe')->firstOrFail();
        $this->assertEquals(10.0, (float) $pain->pack_quantity);
        $this->assertTrue($pain->is_key);
        $this->assertEquals(4.0, (float) $pain->unit_cost);

        $tacos = StockItem::where('name', 'Pain tacos')->firstOrFail();
        $this->assertEquals(108.0, (float) $tacos->pack_quantity);

        $this->assertEquals(10.0, (float) StockItem::where('name', 'Poulet')->firstOrFail()->pack_quantity);

        // Relancer la commande ne remet pas les quantités à zéro.
        $pain->update(['quantity' => 120, 'unit_cost' => 5]);
        $this->artisan('restaurant:articles-cles')->assertSuccessful();
        $this->assertEquals(120.0, (float) $pain->fresh()->quantity);
        $this->assertEquals(5.0, (float) $pain->fresh()->unit_cost);
        $this->assertEquals(3, StockItem::count());
    }

    public function test_a_weekly_balance_is_recorded_and_replaces_the_one_of_the_same_day(): void
    {
        $manager = User::factory()->gerant()->create();

        $this->actingAs($manager)->post(route('balances.store'), [
            'recorded_on' => BusinessDay::today(),
            'cash' => 40000, 'bankily' => 25000, 'masrivi' => 10000, 'sedad' => 5000,
        ])->assertRedirect(route('balances.index'));

        $this->assertEquals(80000.0, BalanceSnapshot::firstOrFail()->total());

        $this->actingAs($manager)->post(route('balances.store'), [
            'recorded_on' => BusinessDay::today(),
            'cash' => 30000, 'bankily' => 25000, 'masrivi' => 10000, 'sedad' => 5000,
        ]);

        $this->assertEquals(1, BalanceSnapshot::count());
        $this->assertEquals(70000.0, BalanceSnapshot::firstOrFail()->total());

        $this->actingAs($manager)->get(route('balances.index'))->assertOk()->assertSee('Argent disponible');
    }

    public function test_the_net_position_ignores_the_stock_and_the_estimate_includes_it(): void
    {
        $manager = User::factory()->gerant()->create();
        $month = Carbon::parse(BusinessDay::today())->format('Y-m');

        BalanceSnapshot::create([
            'user_id' => $manager->id,
            'recorded_on' => BusinessDay::today(),
            'cash' => 50000, 'bankily' => 20000, 'masrivi' => 0, 'sedad' => 0,
        ]);

        $supplier = Supplier::create(['name' => 'Boulangerie']);
        $pain = StockItem::factory()->create(['quantity' => 100, 'unit_cost' => 4]);
        $this->actingAs($manager)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'spent_at' => BusinessDay::today(),
            'payment_method' => 'credit',
            'lines' => [['stock_item_id' => $pain->id, 'quantity' => 100, 'total_price' => 400]],
        ]);
        Staff::create(['name' => 'Aminata', 'job_title' => 'Cuisinière', 'monthly_salary' => 15000]);

        $report = app(ReportService::class)->treasury($month);

        $this->assertEquals(70000.0, $report['available']);
        // 70 000 − 400 de dette − 15 000 de salaires
        $this->assertEquals(54600.0, $report['netPosition']);
        // L'estimation ajoute la valeur du stock : 200 pains × 4 MRU
        $this->assertEquals(54600.0 + 800.0, $report['workingCapital']);
        $this->assertNull($report['stockCountedAt']);

        $this->actingAs($manager)->get(route('reports.treasury'))
            ->assertOk()
            ->assertSee('Position nette')
            ->assertSee('estimation')
            ->assertSee('jamais');
    }

    public function test_the_last_count_date_is_shown_once_an_item_has_been_counted(): void
    {
        $manager = User::factory()->gerant()->create();
        $item = StockItem::factory()->create(['name' => 'Pain arabe', 'quantity' => 100, 'unit_cost' => 4, 'is_key' => true]);

        app(StockService::class)->adjust($item, 95, $manager, 'Comptage du soir');

        $this->assertNotNull($item->fresh()->lastCountedAt());

        $report = app(ReportService::class)->treasury(Carbon::parse(BusinessDay::today())->format('Y-m'));
        $this->assertNotNull($report['stockCountedAt']);
        $this->assertEquals(0, $report['keyItemsNeverCounted']);

        $this->actingAs($manager)->get(route('stock-items.index'))->assertOk()->assertSee('Dernier comptage');
        $this->actingAs($manager)->get(route('reports.material'))->assertOk()->assertSee('Dernier comptage');
    }

    public function test_a_cashier_cannot_record_balances(): void
    {
        $cashier = User::factory()->caissier()->create();

        $this->actingAs($cashier)->get(route('balances.index'))->assertForbidden();
    }
}
