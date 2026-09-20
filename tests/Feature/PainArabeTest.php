<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ReportService;
use App\Services\SaleService;
use App\Support\BusinessDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PainArabeTest extends TestCase
{
    use RefreshDatabase;

    private function menu(): void
    {
        $category = ProductCategory::factory()->create(['name' => 'Snacks']);
        foreach ([['Kebab poulet', 50], ['Kebab viande', 50], ['Kebab spécial', 70], ['Chawarma', 80]] as [$name, $price]) {
            Product::factory()->create([
                'name' => $name,
                'sale_price' => $price,
                'product_category_id' => $category->id,
            ]);
        }
    }

    public function test_the_command_links_the_supplier_the_item_and_the_recipes(): void
    {
        $this->menu();

        $this->artisan('restaurant:pain-arabe')->assertSuccessful();

        $supplier = Supplier::where('name', 'Lassana Camara')->firstOrFail();
        $pain = StockItem::where('name', 'Pain arabe')->firstOrFail();

        $this->assertEquals($supplier->id, $pain->supplier_id);
        // L'unité de suivi est le PAQUET : 10 = 10 paquets = 100 pains.
        $this->assertEquals('paquet', $pain->unit->value);
        $this->assertEquals(40.0, (float) $pain->unit_cost);
        $this->assertTrue($pain->is_key);

        foreach (['Kebab poulet', 'Kebab viande', 'Kebab spécial', 'Chawarma'] as $name) {
            $product = Product::where('name', $name)->firstOrFail();
            // 1 sandwich = 1 pain = 0,1 paquet
            $this->assertEquals(0.1, (float) $product->stockItems()->firstOrFail()->pivot->quantity_needed, $name);
        }

        // Relancer la commande ne casse rien et ne duplique pas.
        $this->artisan('restaurant:pain-arabe')->assertSuccessful();
        $this->assertEquals(1, Supplier::count());
        $this->assertEquals(1, StockItem::count());
    }

    public function test_a_missing_product_is_reported_not_created(): void
    {
        $category = ProductCategory::factory()->create(['name' => 'Snacks']);
        Product::factory()->create(['name' => 'Kebab poulet', 'sale_price' => 50, 'product_category_id' => $category->id]);

        $this->artisan('restaurant:pain-arabe')
            ->expectsOutputToContain('Kebab spécial')
            ->assertSuccessful();

        $this->assertEquals(1, Product::count());
    }

    public function test_the_full_chain_credit_delivery_sales_and_shortage(): void
    {
        $this->menu();
        $this->artisan('restaurant:pain-arabe');

        $manager = User::factory()->gerant()->create();
        $supplier = Supplier::where('name', 'Lassana Camara')->firstOrFail();
        $pain = StockItem::where('name', 'Pain arabe')->firstOrFail();

        // Livraison à crédit : 50 paquets (500 pains) à 2 000 MRU.
        $this->actingAs($manager)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'spent_at' => BusinessDay::today(),
            'payment_method' => 'credit',
            'lines' => [['stock_item_id' => $pain->id, 'quantity' => 50, 'total_price' => 2000]],
        ])->assertRedirect();

        $this->assertEquals(50.0, (float) $pain->fresh()->quantity);      // 50 paquets
        $this->assertEquals(40.0, (float) $pain->fresh()->unit_cost);     // 40 MRU le paquet
        $this->assertEquals(2000.0, $supplier->fresh()->balance());

        // 100 kebabs poulet + 50 chawarmas = 150 pains consommés.
        $kebab = Product::where('name', 'Kebab poulet')->firstOrFail();
        $chawarma = Product::where('name', 'Chawarma')->firstOrFail();
        app(SaleService::class)->record($manager, [
            ['product_id' => $kebab->id, 'quantity' => 100],
            ['product_id' => $chawarma->id, 'quantity' => 50],
        ], PaymentMethod::Especes, null);

        // 150 pains = 15 paquets : il reste 35 paquets.
        $this->assertEquals(35.0, (float) $pain->fresh()->quantity);

        // Comptage : il ne reste que 33 paquets → 2 paquets (20 pains) disparus.
        $this->actingAs($manager)->post(route('reconciliation.store'), ['counted' => [$pain->id => 33]])->assertOk();

        $material = app(ReportService::class)->material(BusinessDay::today(), BusinessDay::today());
        $row = $material['rows']->firstWhere('item.id', $pain->id);

        $this->assertEquals(50.0, $row['purchaseQty']);    // paquets achetés
        $this->assertEquals(15.0, $row['consumedQty']);    // paquets consommés par les ventes
        $this->assertEquals(2.0, $row['missingQty']);      // 2 paquets manquants
        $this->assertEquals(80.0, $row['missingValue']);   // 2 paquets × 40 MRU
    }

    public function test_the_usual_supplier_is_offered_on_the_purchase_screen(): void
    {
        $this->menu();
        $this->artisan('restaurant:pain-arabe');
        $manager = User::factory()->gerant()->create();

        $this->actingAs($manager)->get(route('purchases.create'))
            ->assertOk()
            ->assertSee('Lassana Camara')
            ->assertSee('supplier_id', false);
    }

    public function test_the_minced_meat_command_links_supplier_item_and_recipes(): void
    {
        $category = \App\Models\ProductCategory::factory()->create(['name' => 'Snacks']);
        foreach ([['Kebab viande', 50], ['Tacos viande', 140], ['Pizza viande', 200], ['Hamburger', 120]] as [$name, $price]) {
            Product::factory()->create(['name' => $name, 'sale_price' => $price, 'product_category_id' => $category->id]);
        }

        $this->artisan('restaurant:viande-hachee')
            ->expectsOutputToContain('Kebab spécial')   // pas encore créé : signalé, pas inventé
            ->assertSuccessful();

        $supplier = Supplier::where('name', 'Fabe Cissé')->firstOrFail();
        $viande = StockItem::where('name', 'Viande hachée')->firstOrFail();

        $this->assertEquals($supplier->id, $viande->supplier_id);
        $this->assertTrue($viande->is_key);
        $this->assertEquals('kg', $viande->unit->value);

        $kebab = Product::where('name', 'Kebab viande')->firstOrFail();
        $this->assertEquals(0.12, (float) $kebab->stockItems()->firstOrFail()->pivot->quantity_needed);
        $this->assertEquals(0.15, (float) Product::where('name', 'Tacos viande')->firstOrFail()->stockItems()->firstOrFail()->pivot->quantity_needed);

        // Les grammages sont réglables sans toucher au code.
        $this->artisan('restaurant:viande-hachee', ['--kebab' => 0.2])->assertSuccessful();
        $this->assertEquals(0.2, (float) $kebab->fresh()->stockItems()->firstOrFail()->pivot->quantity_needed);
    }

    public function test_a_product_can_use_both_bread_and_meat(): void
    {
        $this->menu();
        $this->artisan('restaurant:pain-arabe');
        $this->artisan('restaurant:viande-hachee');

        $kebab = Product::where('name', 'Kebab viande')->firstOrFail();
        $ingredients = $kebab->stockItems()->pluck('name')->all();

        // La deuxième commande n'a pas effacé la recette de la première.
        $this->assertContains('Pain arabe', $ingredients);
        $this->assertContains('Viande hachée', $ingredients);

        $manager = User::factory()->gerant()->create();
        $pain = StockItem::where('name', 'Pain arabe')->firstOrFail();
        $viande = StockItem::where('name', 'Viande hachée')->firstOrFail();
        $pain->update(['quantity' => 10]);                                // 10 paquets = 100 pains
        $viande->update(['quantity' => 10, 'unit_cost' => 500]);

        app(SaleService::class)->record($manager, [['product_id' => $kebab->id, 'quantity' => 10]], PaymentMethod::Especes, null);

        $this->assertEquals(9.0, (float) $pain->fresh()->quantity);        // 10 pains = 1 paquet
        $this->assertEquals(8.8, (float) $viande->fresh()->quantity);      // 10 × 0,12 kg
    }

    public function test_an_item_already_tracked_in_pieces_is_converted_to_packs(): void
    {
        $this->menu();

        // Ancienne configuration : pain suivi à la pièce, 200 pains à 4 MRU, recette de 1 pain.
        $pain = StockItem::factory()->create([
            'name' => 'Pain arabe', 'unit' => 'piece', 'quantity' => 200, 'unit_cost' => 4,
        ]);
        $kebab = Product::where('name', 'Kebab poulet')->firstOrFail();
        $kebab->stockItems()->attach($pain->id, ['quantity_needed' => 1]);

        $this->artisan('restaurant:pain-arabe')->assertSuccessful();

        $pain->refresh();
        $this->assertEquals('paquet', $pain->unit->value);
        $this->assertEquals(20.0, (float) $pain->quantity);    // 200 pains = 20 paquets
        $this->assertEquals(40.0, (float) $pain->unit_cost);   // 4 MRU le pain = 40 le paquet
        $this->assertEquals(0.1, (float) $kebab->fresh()->stockItems()->firstOrFail()->pivot->quantity_needed);
    }
}
