<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Models\User;
use App\Support\BusinessDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_supplier_can_be_renamed_and_given_a_phone_number(): void
    {
        $manager = User::factory()->gerant()->create();
        $supplier = Supplier::create(['name' => 'Lassana', 'is_active' => true]);

        $this->actingAs($manager)
            ->from(route('suppliers.index'))
            ->put(route('suppliers.update', $supplier), [
                'name' => 'Lassana Camara',
                'phone' => '49 62 53 25',
                'note' => 'Pain arabe, livré le matin',
                'is_active' => '1',
            ])
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('status');

        $supplier->refresh();
        $this->assertEquals('Lassana Camara', $supplier->name);
        $this->assertEquals('49 62 53 25', $supplier->phone);
        $this->assertTrue($supplier->is_active);
    }

    public function test_a_supplier_can_be_deactivated_instead_of_deleted(): void
    {
        $manager = User::factory()->gerant()->create();
        $supplier = Supplier::create(['name' => 'Ancien boulanger', 'is_active' => true]);

        $this->actingAs($manager)
            ->from(route('suppliers.index'))
            ->put(route('suppliers.update', $supplier), ['name' => 'Ancien boulanger']);

        $this->assertFalse($supplier->fresh()->is_active);
    }

    public function test_two_suppliers_cannot_share_a_name(): void
    {
        $manager = User::factory()->gerant()->create();
        Supplier::create(['name' => 'Fabe Cissé']);
        $other = Supplier::create(['name' => 'Lassana Camara']);

        $this->actingAs($manager)
            ->from(route('suppliers.index'))
            ->put(route('suppliers.update', $other), ['name' => 'Fabe Cissé'])
            ->assertSessionHasErrors('name');

        $this->assertEquals('Lassana Camara', $other->fresh()->name);
    }

    public function test_an_unused_supplier_can_be_deleted_and_its_items_are_freed(): void
    {
        $manager = User::factory()->gerant()->create();
        $supplier = Supplier::create(['name' => 'Erreur de saisie']);
        $item = StockItem::factory()->create(['name' => 'Sucre', 'supplier_id' => $supplier->id]);

        $this->actingAs($manager)
            ->from(route('suppliers.index'))
            ->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('status');

        $this->assertNull(Supplier::find($supplier->id));
        // L'article reste, simplement sans fournisseur : on ne perd pas le stock.
        $this->assertNull($item->fresh()->supplier_id);
    }

    public function test_a_supplier_with_history_is_never_deleted(): void
    {
        $manager = User::factory()->gerant()->create();
        $supplier = Supplier::create(['name' => 'Lassana Camara']);
        $item = StockItem::factory()->create([
            'name' => 'Pain arabe',
            'unit' => 'paquet',
            'quantity' => 0,
            'unit_cost' => 40,
            'supplier_id' => $supplier->id,
            'agreed_unit_price' => 40,
            'default_payment_method' => PaymentMethod::Credit,
        ]);

        $this->actingAs($manager)
            ->from(route('purchases.create'))
            ->post(route('purchases.quick', $item), ['quantity' => 30]);

        $this->actingAs($manager)
            ->from(route('suppliers.index'))
            ->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('error');

        // L'historique est intact : supprimer aurait effacé 1 200 MRU de dette.
        $this->assertNotNull(Supplier::find($supplier->id));
        $this->assertEquals(1200.0, $supplier->fresh()->balance());
    }

    public function test_a_payment_entered_by_mistake_can_be_deleted(): void
    {
        $manager = User::factory()->gerant()->create();
        $supplier = Supplier::create(['name' => 'Fabe Cissé']);

        // Montant d'essai saisi pendant la mise en route.
        $this->actingAs($manager)->post(route('suppliers.pay', $supplier), [
            'amount' => 10560,
            'payment_method' => 'especes',
            'paid_at' => BusinessDay::today(),
        ]);

        $this->assertEquals(-10560.0, $supplier->fresh()->balance());

        $payment = \App\Models\SupplierPayment::latest('id')->firstOrFail();

        $this->actingAs($manager)
            ->from(route('suppliers.show', $supplier))
            ->delete(route('suppliers.payments.destroy', [$supplier, $payment]))
            ->assertRedirect(route('suppliers.show', $supplier))
            ->assertSessionHas('status');

        $this->assertEquals(0.0, $supplier->fresh()->balance());
        $this->assertEquals(0, \App\Models\SupplierPayment::count());
    }

    public function test_a_payment_cannot_be_deleted_from_another_supplier(): void
    {
        $manager = User::factory()->gerant()->create();
        $lassana = Supplier::create(['name' => 'Lassana Camara']);
        $fabe = Supplier::create(['name' => 'Fabe Cissé']);

        $this->actingAs($manager)->post(route('suppliers.pay', $fabe), [
            'amount' => 5000,
            'payment_method' => 'especes',
            'paid_at' => BusinessDay::today(),
        ]);

        $payment = \App\Models\SupplierPayment::latest('id')->firstOrFail();

        $this->actingAs($manager)
            ->from(route('suppliers.show', $lassana))
            ->delete(route('suppliers.payments.destroy', [$lassana, $payment]))
            ->assertSessionHas('error');

        $this->assertEquals(1, \App\Models\SupplierPayment::count());
    }

    public function test_the_purchase_page_names_the_items_that_are_half_configured(): void
    {
        $manager = User::factory()->gerant()->create();
        $supplier = Supplier::create(['name' => 'Lassana Camara']);

        // Fournisseur choisi, mais pas de prix convenu : la prise du jour ne peut pas
        // apparaître. C'est exactement le cas où « rien ne bouge » sans explication.
        StockItem::factory()->create([
            'name' => 'Pain arabe',
            'supplier_id' => $supplier->id,
            'agreed_unit_price' => null,
        ]);

        $this->actingAs($manager)->get(route('purchases.create'))
            ->assertOk()
            ->assertSee('Paramétrage incomplet')
            ->assertSee('Pain arabe')
            ->assertSee('prix convenu par unité', false);
    }

    public function test_the_check_command_reports_a_broken_chain(): void
    {
        $supplier = Supplier::create(['name' => 'Lassana Camara']);
        StockItem::factory()->create([
            'name' => 'Pain arabe',
            'supplier_id' => $supplier->id,
            'agreed_unit_price' => null,
        ]);

        $this->artisan('restaurant:verifier')
            ->expectsOutputToContain('Pain arabe')
            ->expectsOutputToContain('prix convenu')
            ->assertSuccessful();
    }

    public function test_the_check_command_confirms_a_complete_chain(): void
    {
        $supplier = Supplier::create(['name' => 'Lassana Camara']);
        StockItem::factory()->create([
            'name' => 'Pain arabe',
            'supplier_id' => $supplier->id,
            'agreed_unit_price' => 40,
            'default_payment_method' => PaymentMethod::Credit,
        ]);

        $this->artisan('restaurant:verifier')
            ->expectsOutputToContain('Chaîne complète')
            ->assertSuccessful();
    }

    public function test_a_credit_taking_is_visible_on_the_supplier_statement(): void
    {
        $manager = User::factory()->gerant()->create();
        $supplier = Supplier::create(['name' => 'Lassana Camara']);
        $item = StockItem::factory()->create([
            'name' => 'Pain arabe',
            'unit' => 'paquet',
            'quantity' => 0,
            'unit_cost' => 40,
            'supplier_id' => $supplier->id,
            'agreed_unit_price' => 40,
            'default_payment_method' => PaymentMethod::Credit,
        ]);

        $this->actingAs($manager)
            ->from(route('purchases.create'))
            ->post(route('purchases.quick', $item), ['quantity' => 30]);

        $this->actingAs($manager)->get(route('suppliers.show', $supplier))
            ->assertOk()
            ->assertSee('Pain arabe')
            ->assertSee('1 200');

        $this->actingAs($manager)->get(route('suppliers.index'))
            ->assertOk()
            ->assertSee('1 200');
    }
}
