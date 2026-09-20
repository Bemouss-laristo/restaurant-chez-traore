<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\StockMovementReason;
use App\Models\CashSession;
use App\Models\Expense;
use App\Models\Product;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CashSessionService;
use App\Services\ReportService;
use App\Services\SaleService;
use App\Support\BusinessDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    private function painTacos(): StockItem
    {
        // 1 carton = 6 sachets × 18 pains = 108 pains.
        return StockItem::factory()->create([
            'name' => 'Pain tacos',
            'unit' => 'piece',
            'quantity' => 0,
            'unit_cost' => 0,
            'pack_label' => 'Carton (6 sachets × 18)',
            'pack_quantity' => 108,
            'is_key' => true,
        ]);
    }

    public function test_a_purchase_adds_stock_and_records_the_expense_in_one_step(): void
    {
        $manager = User::factory()->gerant()->create();
        $session = CashSession::factory()->create(['user_id' => $manager->id, 'opening_float' => 20000]);
        $item = $this->painTacos();

        $this->actingAs($manager)
            ->post(route('purchases.store'), [
                'supplier_id' => Supplier::create(['name' => 'Boulangerie Traoré'])->id,
                'spent_at' => BusinessDay::today(),
                'payment_method' => 'especes',
                'lines' => [
                    ['stock_item_id' => $item->id, 'pack' => 1, 'quantity' => 2, 'total_price' => 5400],
                ],
            ])
            ->assertRedirect(route('purchases.create'));

        // 2 cartons = 216 pains, à 25 MRU l'unité.
        $item->refresh();
        $this->assertEquals(216.0, (float) $item->quantity);
        $this->assertEquals(25.0, (float) $item->unit_cost);

        $expense = Expense::firstOrFail();
        $this->assertEquals(5400.0, (float) $expense->amount);
        $this->assertEquals('achat_marchandises', $expense->expense_category->value);
        $this->assertEquals($session->id, $expense->cash_session_id);
        $this->assertStringContainsString('Boulangerie Traoré', $expense->description);

        // L'argent est bien sorti de la caisse.
        $this->assertEquals(14600.0, app(CashSessionService::class)->expectedCash($session));

        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $item->id,
            'reason' => StockMovementReason::Purchase->value,
            'unit_cost' => 25.00,
        ]);
    }

    public function test_the_unit_cost_is_a_weighted_average(): void
    {
        $manager = User::factory()->gerant()->create();
        $item = StockItem::factory()->create(['quantity' => 100, 'unit_cost' => 4, 'pack_quantity' => null]);

        $this->actingAs($manager)->post(route('purchases.store'), [
            'spent_at' => BusinessDay::today(),
            'payment_method' => 'bankily',
            'lines' => [['stock_item_id' => $item->id, 'quantity' => 100, 'total_price' => 600]],
        ]);

        // (100 × 4 + 600) / 200 = 5 MRU
        $this->assertEquals(5.0, (float) $item->fresh()->unit_cost);
    }

    public function test_the_material_report_shows_what_was_bought_sold_and_missing(): void
    {
        $manager = User::factory()->gerant()->create();
        $pain = StockItem::factory()->create(['name' => 'Pain arabe', 'unit' => 'piece', 'quantity' => 0, 'unit_cost' => 0, 'pack_label' => 'Paquet de 10', 'pack_quantity' => 10, 'is_key' => true]);

        // Achat : 200 pains à 4 MRU (20 paquets à 40 MRU).
        $this->actingAs($manager)->post(route('purchases.store'), [
            'spent_at' => BusinessDay::today(),
            'payment_method' => 'especes',
            'lines' => [['stock_item_id' => $pain->id, 'pack' => 1, 'quantity' => 20, 'total_price' => 800]],
        ]);

        // Vente de 120 kebabs : 1 pain chacun.
        $kebab = Product::factory()->create(['name' => 'Kebab', 'sale_price' => 100, 'estimated_cost' => 40]);
        $kebab->stockItems()->attach($pain->id, ['quantity_needed' => 1]);
        app(SaleService::class)->record($manager, [['product_id' => $kebab->id, 'quantity' => 120]], PaymentMethod::Especes, null);

        // Comptage du soir : il ne reste que 50 pains au lieu de 80 → 30 disparus.
        $this->actingAs($manager)->post(route('reconciliation.store'), ['counted' => [$pain->id => 50]])->assertOk();

        $report = app(ReportService::class)->material(BusinessDay::today(), BusinessDay::today());
        $row = $report['rows']->firstWhere('item.id', $pain->id);

        $this->assertEquals(200.0, $row['purchaseQty']);
        $this->assertEquals(800.0, $row['purchaseValue']);
        $this->assertEquals(120.0, $row['consumedQty']);
        $this->assertEquals(30.0, $row['missingQty']);
        $this->assertEquals(120.0, $row['missingValue']); // 30 pains × 4 MRU
        $this->assertEquals(50.0, $row['finalQty']);
        $this->assertEqualsWithDelta(15.0, $row['lossRate'], 0.1); // 30 / 200

        $this->actingAs($manager)->get(route('reports.material'))
            ->assertOk()
            ->assertSee('Pain arabe')
            ->assertSee('Contrôle matière')
            ->assertSee('Kebab');
    }

    public function test_the_nightly_count_only_asks_for_key_items(): void
    {
        $manager = User::factory()->gerant()->create();
        $this->painTacos();
        StockItem::factory()->create(['name' => 'Sel fin', 'is_key' => false]);

        $this->actingAs($manager)->get(route('reconciliation.index'))
            ->assertOk()
            ->assertSee('Pain tacos')
            ->assertDontSee('Sel fin');

        $this->actingAs($manager)->get(route('reconciliation.index', ['all' => 1]))
            ->assertOk()
            ->assertSee('Sel fin');
    }

    public function test_a_cashier_cannot_record_purchases(): void
    {
        $cashier = User::factory()->caissier()->create();
        $item = $this->painTacos();

        $this->actingAs($cashier)->get(route('purchases.create'))->assertForbidden();
        $this->actingAs($cashier)->post(route('purchases.store'), [
            'spent_at' => BusinessDay::today(),
            'payment_method' => 'especes',
            'lines' => [['stock_item_id' => $item->id, 'quantity' => 1, 'total_price' => 100]],
        ])->assertForbidden();
    }

    public function test_manager_records_an_expense_as_a_list_of_items(): void
    {
        $manager = User::factory()->gerant()->create();

        $this->actingAs($manager)->post(route('expenses.store'), [
            'expense_category' => 'divers',
            'spent_at' => BusinessDay::today(),
            'payment_method' => 'especes',
            'items' => [
                ['label' => 'Sac de charbon', 'price' => 1200],
                ['label' => 'Gants', 'price' => 300],
            ],
        ])->assertRedirect(route('expenses.index'));

        $expense = Expense::firstOrFail();
        $this->assertEquals(1500.0, (float) $expense->amount);
        $this->assertStringContainsString('Sac de charbon — 1 200 MRU', $expense->description);

        // La modification rouvre bien les lignes existantes.
        $this->actingAs($manager)->get(route('expenses.edit', $expense))
            ->assertOk()
            ->assertSee('Sac de charbon');
    }

    public function test_the_dashboard_lists_every_product_sold_this_month(): void
    {
        $manager = User::factory()->gerant()->create();
        $kebab = Product::factory()->create(['name' => 'Kebab', 'sale_price' => 100, 'estimated_cost' => 40]);
        app(SaleService::class)->record($manager, [['product_id' => $kebab->id, 'quantity' => 3]], PaymentMethod::Especes, null);

        $this->actingAs($manager)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Produits vendus ce mois')
            ->assertSee('Kebab');
    }
}
