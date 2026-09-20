<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Models\User;
use App\Support\BusinessDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le lien fournisseur ↔ article, et la question à laquelle il doit répondre :
 * « combien ai-je pris chaque jour, et combien dois-je à Lassana en fin de mois ? »
 */
class SupplierItemLinkTest extends TestCase
{
    use RefreshDatabase;

    private function menu(): void
    {
        $category = ProductCategory::factory()->create(['name' => 'Snacks']);
        foreach ([['Kebab poulet', 50], ['Kebab viande', 50], ['Kebab spécial', 70], ['Chawarma', 80]] as [$name, $price]) {
            Product::factory()->create(['name' => $name, 'sale_price' => $price, 'product_category_id' => $category->id]);
        }
    }

    public function test_arab_bread_has_an_agreed_price_and_is_taken_on_credit(): void
    {
        $this->menu();
        $this->artisan('restaurant:pain-arabe')->assertSuccessful();

        $pain = StockItem::where('name', 'Pain arabe')->firstOrFail();
        $supplier = Supplier::where('name', 'Lassana Camara')->firstOrFail();

        $this->assertEquals($supplier->id, $pain->supplier_id);
        $this->assertEquals(PaymentMethod::Credit, $pain->default_payment_method);
        $this->assertEquals(40.0, (float) $pain->agreed_unit_price);
        $this->assertTrue($pain->hasAgreedPrice());
        // La quantité change tous les jours : rien n'est pré-rempli.
        $this->assertNull($pain->dailyTotal());
    }

    public function test_minced_meat_is_two_kilos_a_day_at_the_agreed_price(): void
    {
        $this->artisan('restaurant:viande-hachee')->assertSuccessful();

        $viande = StockItem::where('name', 'Viande hachée')->firstOrFail();

        $this->assertEquals(220.0, (float) $viande->agreed_unit_price);
        $this->assertEquals(2.0, (float) $viande->daily_quantity);
        $this->assertEquals(440.0, $viande->dailyTotal());   // 2 kg × 220 MRU
        $this->assertEquals(PaymentMethod::Credit, $viande->default_payment_method);
    }

    public function test_the_daily_entry_only_asks_for_the_quantity(): void
    {
        $this->menu();
        $this->artisan('restaurant:pain-arabe');
        $manager = User::factory()->gerant()->create();
        $pain = StockItem::where('name', 'Pain arabe')->firstOrFail();
        $supplier = Supplier::where('name', 'Lassana Camara')->firstOrFail();

        // 30 paquets pris aujourd'hui : le prix ne se saisit pas, il est convenu.
        $this->actingAs($manager)
            ->from(route('purchases.create'))
            ->post(route('purchases.quick', $pain), ['quantity' => 30])
            ->assertRedirect(route('purchases.create'))
            ->assertSessionHas('status');

        $this->assertEquals(30.0, (float) $pain->fresh()->quantity);
        $this->assertEquals(1200.0, $supplier->fresh()->balance());   // 30 × 40 MRU
        $this->assertEquals(30.0, $pain->fresh()->takenToday());

        // Deuxième passage du livreur le même jour : ça s'ajoute, ça ne remplace pas.
        $this->actingAs($manager)
            ->from(route('purchases.create'))
            ->post(route('purchases.quick', $pain), ['quantity' => 10]);

        $this->assertEquals(40.0, (float) $pain->fresh()->quantity);
        $this->assertEquals(1600.0, $supplier->fresh()->balance());
        $this->assertEquals(40.0, $pain->fresh()->takenToday());
    }

    public function test_the_quantity_is_required_and_must_be_positive(): void
    {
        $this->menu();
        $this->artisan('restaurant:pain-arabe');
        $manager = User::factory()->gerant()->create();
        $pain = StockItem::where('name', 'Pain arabe')->firstOrFail();

        $this->actingAs($manager)
            ->from(route('purchases.create'))
            ->post(route('purchases.quick', $pain), ['quantity' => 0])
            ->assertSessionHasErrors('quantity');

        $this->assertEquals(0.0, (float) $pain->fresh()->quantity);
    }

    public function test_an_item_without_an_agreed_price_is_refused(): void
    {
        $manager = User::factory()->gerant()->create();
        $item = StockItem::factory()->create(['name' => 'Gaz', 'quantity' => 0]);

        $this->actingAs($manager)
            ->from(route('purchases.create'))
            ->post(route('purchases.quick', $item), ['quantity' => 3])
            ->assertRedirect(route('purchases.create'))
            ->assertSessionHas('error');

        $this->assertEquals(0.0, (float) $item->fresh()->quantity);
    }

    public function test_the_supplier_statement_shows_what_was_taken_day_by_day_and_what_to_pay(): void
    {
        $this->menu();
        $this->artisan('restaurant:pain-arabe');
        $manager = User::factory()->gerant()->create();
        $pain = StockItem::where('name', 'Pain arabe')->firstOrFail();
        $supplier = Supplier::where('name', 'Lassana Camara')->firstOrFail();

        foreach ([30, 25, 40] as $quantity) {
            $this->actingAs($manager)
                ->from(route('purchases.create'))
                ->post(route('purchases.quick', $pain), ['quantity' => $quantity]);
        }

        // 95 paquets × 40 MRU = 3 800 MRU à payer en fin de mois.
        $this->assertEquals(3800.0, $supplier->fresh()->balance());

        $this->actingAs($manager)->get(route('suppliers.show', $supplier))
            ->assertOk()
            ->assertSee('Total pris ce mois')
            ->assertSee('Détail jour par jour')
            ->assertSee('Pain arabe')
            ->assertSee('95')            // quantité totale prise
            ->assertSee('3 800');        // montant dû
    }

    public function test_the_purchase_screen_offers_the_daily_entry(): void
    {
        $this->menu();
        $this->artisan('restaurant:pain-arabe');
        $this->artisan('restaurant:viande-hachee');
        $manager = User::factory()->gerant()->create();

        $this->actingAs($manager)->get(route('purchases.create'))
            ->assertOk()
            ->assertSee('Prise du jour')
            ->assertSee('Pain arabe')
            ->assertSee('Lassana Camara')
            ->assertSee('Viande hachée')
            ->assertSee('Fabe Cissé');
    }

    public function test_paying_the_month_ahead_creates_an_advance_that_takings_consume(): void
    {
        $this->artisan('restaurant:viande-hachee');
        $manager = User::factory()->gerant()->create();
        $viande = StockItem::where('name', 'Viande hachée')->firstOrFail();
        $supplier = Supplier::where('name', 'Fabe Cissé')->firstOrFail();

        // Début du mois : 30 jours × 440 MRU payés d'avance.
        $this->actingAs($manager)->post(route('suppliers.pay', $supplier), [
            'amount' => 13200,
            'payment_method' => 'especes',
            'paid_at' => BusinessDay::today(),
        ])->assertRedirect();

        $this->assertEquals(-13200.0, $supplier->fresh()->balance());
        $this->assertTrue($supplier->fresh()->hasAdvance());

        // La prise du jour mange l'avance au lieu de créer une dette.
        $this->actingAs($manager)
            ->from(route('purchases.create'))
            ->post(route('purchases.quick', $viande), ['quantity' => 2]);

        $this->assertEquals(-12760.0, $supplier->fresh()->balance());
        $this->assertEquals(12760.0, $supplier->fresh()->advance());

        $this->actingAs($manager)->get(route('suppliers.show', $supplier))
            ->assertOk()
            ->assertSee('Avance disponible');
    }

    public function test_the_habits_can_be_set_by_hand_on_the_item_form(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = Supplier::create(['name' => 'Boulangerie Sidi']);

        $this->actingAs($admin)->post(route('stock-items.store'), [
            'name' => 'Lait',
            'unit' => 'litre',
            'quantity' => 0,
            'alert_threshold' => 2,
            'unit_cost' => 30,
            'supplier_id' => $supplier->id,
            'default_payment_method' => 'credit',
            'agreed_unit_price' => 30,
            'daily_quantity' => 5,
        ])->assertRedirect(route('stock-items.index'));

        $item = StockItem::where('name', 'Lait')->firstOrFail();
        $this->assertEquals($supplier->id, $item->supplier_id);
        $this->assertEquals(PaymentMethod::Credit, $item->default_payment_method);
        $this->assertTrue($item->hasAgreedPrice());
        $this->assertEquals(150.0, $item->dailyTotal());

        // Champs vidés : le prix convenu disparaît, il ne reste pas un zéro trompeur.
        $this->actingAs($admin)->put(route('stock-items.update', $item), [
            'name' => 'Lait',
            'unit' => 'litre',
            'alert_threshold' => 2,
            'unit_cost' => 30,
            'supplier_id' => '',
            'default_payment_method' => '',
            'agreed_unit_price' => '',
            'daily_quantity' => '',
        ])->assertRedirect(route('stock-items.index'));

        $item->refresh();
        $this->assertNull($item->supplier_id);
        $this->assertNull($item->default_payment_method);
        $this->assertFalse($item->hasAgreedPrice());
    }
}
