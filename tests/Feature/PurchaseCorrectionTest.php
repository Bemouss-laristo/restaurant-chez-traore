<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\StockMovementReason;
use App\Models\Expense;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ReportService;
use App\Support\BusinessDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Une erreur de frappe sur la quantité prise doit pouvoir se corriger, sinon la
 * dette du fournisseur devient fausse et plus personne ne fait confiance au relevé.
 */
class PurchaseCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private function bread(): StockItem
    {
        return StockItem::factory()->create([
            'name' => 'Pain arabe',
            'unit' => 'paquet',
            'quantity' => 0,
            'unit_cost' => 40,
            'supplier_id' => Supplier::create(['name' => 'Lassana Camara'])->id,
            'agreed_unit_price' => 40,
            'default_payment_method' => PaymentMethod::Credit,
        ]);
    }

    public function test_a_mistyped_quantity_can_be_corrected(): void
    {
        $manager = User::factory()->gerant()->create();
        $item = $this->bread();
        $supplier = $item->supplier;

        // On tape 300 au lieu de 30.
        $this->actingAs($manager)->from(route('purchases.create'))
            ->post(route('purchases.quick', $item), ['quantity' => 300]);

        $this->assertEquals(12000.0, $supplier->fresh()->balance());

        $expense = Expense::latest('id')->firstOrFail();

        $this->actingAs($manager)->from(route('purchases.create'))
            ->put(route('purchases.update', $expense), ['quantity' => 30])
            ->assertRedirect(route('purchases.create'))
            ->assertSessionHas('status');

        // Stock et dette reviennent à la vérité, sans double comptage.
        $this->assertEquals(30.0, (float) $item->fresh()->quantity);
        $this->assertEquals(1200.0, $supplier->fresh()->balance());
        $this->assertEquals(1, Expense::count());
        $this->assertEquals(1, StockMovement::where('reason', StockMovementReason::Purchase->value)->count());
    }

    public function test_a_wrong_entry_can_be_deleted_and_leaves_no_trace(): void
    {
        $manager = User::factory()->gerant()->create();
        $item = $this->bread();
        $supplier = $item->supplier;

        $this->actingAs($manager)->from(route('purchases.create'))
            ->post(route('purchases.quick', $item), ['quantity' => 30]);

        $expense = Expense::latest('id')->firstOrFail();

        $this->actingAs($manager)->from(route('purchases.create'))
            ->delete(route('purchases.destroy', $expense))
            ->assertRedirect(route('purchases.create'))
            ->assertSessionHas('status');

        $this->assertEquals(0.0, (float) $item->fresh()->quantity);
        $this->assertEquals(0.0, $supplier->fresh()->balance());
        $this->assertEquals(0, Expense::count());

        // Le contrôle matière ne doit pas garder l'achat fantôme.
        $material = app(ReportService::class)->material(BusinessDay::today(), BusinessDay::today());
        $row = $material['rows']->firstWhere('item.id', $item->id);
        $this->assertEquals(0.0, $row['purchaseQty']);
    }

    public function test_a_multi_line_purchase_is_not_corrected_line_by_line(): void
    {
        $manager = User::factory()->gerant()->create();
        $bread = $this->bread();
        $other = StockItem::factory()->create(['name' => 'Gaz', 'quantity' => 0, 'unit_cost' => 0]);

        $this->actingAs($manager)->post(route('purchases.store'), [
            'spent_at' => BusinessDay::today(),
            'payment_method' => 'especes',
            'lines' => [
                ['stock_item_id' => $bread->id, 'quantity' => 10, 'total_price' => 400],
                ['stock_item_id' => $other->id, 'quantity' => 1, 'total_price' => 2500],
            ],
        ]);

        $expense = Expense::latest('id')->firstOrFail();

        $this->actingAs($manager)->from(route('purchases.create'))
            ->put(route('purchases.update', $expense), ['quantity' => 5])
            ->assertSessionHas('error');

        $this->assertEquals(10.0, (float) $bread->fresh()->quantity);
    }

    public function test_deleting_a_multi_line_purchase_reverses_every_line(): void
    {
        $manager = User::factory()->gerant()->create();
        $bread = $this->bread();
        $other = StockItem::factory()->create(['name' => 'Gaz', 'quantity' => 0, 'unit_cost' => 0]);

        $this->actingAs($manager)->post(route('purchases.store'), [
            'spent_at' => BusinessDay::today(),
            'payment_method' => 'especes',
            'lines' => [
                ['stock_item_id' => $bread->id, 'quantity' => 10, 'total_price' => 400],
                ['stock_item_id' => $other->id, 'quantity' => 1, 'total_price' => 2500],
            ],
        ]);

        $this->actingAs($manager)->from(route('purchases.create'))
            ->delete(route('purchases.destroy', Expense::latest('id')->firstOrFail()));

        $this->assertEquals(0.0, (float) $bread->fresh()->quantity);
        $this->assertEquals(0.0, (float) $other->fresh()->quantity);
    }

    public function test_an_item_and_a_brand_new_supplier_can_be_added_from_the_purchase_page(): void
    {
        $manager = User::factory()->gerant()->create();
        $chicken = StockItem::factory()->create(['name' => 'Poulet', 'unit' => 'piece', 'quantity' => 0]);

        $this->actingAs($manager)->from(route('purchases.create'))
            ->post(route('purchases.configure'), [
                'stock_item_id' => $chicken->id,
                'new_supplier' => 'Aviculture Sidi',
                'new_supplier_phone' => '22 33 44 55',
                'agreed_unit_price' => 350,
                'daily_quantity' => 20,
                'payment_method' => 'credit',
            ])
            ->assertRedirect(route('purchases.create'))
            ->assertSessionHas('status');

        $supplier = Supplier::where('name', 'Aviculture Sidi')->firstOrFail();
        $chicken->refresh();

        $this->assertEquals($supplier->id, $chicken->supplier_id);
        $this->assertEquals(350.0, (float) $chicken->agreed_unit_price);
        $this->assertEquals(20.0, (float) $chicken->daily_quantity);
        $this->assertEquals(PaymentMethod::Credit, $chicken->default_payment_method);
        $this->assertEquals('22 33 44 55', $supplier->phone);

        // Il apparaît désormais dans la prise du jour.
        $this->actingAs($manager)->get(route('purchases.create'))
            ->assertOk()
            ->assertSee('Aviculture Sidi');

        // Et une prise alimente bien son compte.
        $this->actingAs($manager)->from(route('purchases.create'))
            ->post(route('purchases.quick', $chicken), ['quantity' => 20]);

        $this->assertEquals(7000.0, $supplier->fresh()->balance());
    }

    public function test_adding_without_any_supplier_is_refused(): void
    {
        $manager = User::factory()->gerant()->create();
        $item = StockItem::factory()->create(['name' => 'Poulet', 'quantity' => 0]);

        $this->actingAs($manager)->from(route('purchases.create'))
            ->post(route('purchases.configure'), [
                'stock_item_id' => $item->id,
                'agreed_unit_price' => 350,
                'payment_method' => 'credit',
            ])
            ->assertSessionHas('error');

        $this->assertNull($item->fresh()->supplier_id);
    }
}
