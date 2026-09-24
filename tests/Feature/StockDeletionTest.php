<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\StockMovementReason;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockItem;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Supprimer un article ou un produit doit marcher en vrai, pas seulement sur
 * une base vide : en production presque tout a déjà un historique.
 */
class StockDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unused_item_is_deleted_for_good(): void
    {
        $admin = User::factory()->admin()->create();
        $item = StockItem::factory()->create(['name' => 'Article en trop']);

        $this->actingAs($admin)
            ->delete(route('stock-items.destroy', $item))
            ->assertRedirect(route('stock-items.index'))
            ->assertSessionHas('status');

        $this->assertNull(StockItem::withTrashed()->find($item->id));
    }

    public function test_an_item_with_history_is_archived_not_refused(): void
    {
        $admin = User::factory()->admin()->create();
        $item = StockItem::factory()->create(['name' => 'Farine', 'quantity' => 0]);
        app(StockService::class)->addStock($item, 10, StockMovementReason::Purchase, $admin);

        $this->actingAs($admin)
            ->delete(route('stock-items.destroy', $item))
            ->assertRedirect(route('stock-items.index'))
            ->assertSessionHas('status');

        // Disparu des listes…
        $this->assertNull(StockItem::find($item->id));
        // (Le nom reste visible dans le message de confirmation : on vérifie la ligne elle-même.)
        $this->actingAs($admin)->get(route('stock-items.index'))->assertOk()
            ->assertDontSee(route('stock-items.edit', $item), false);

        // …mais l'historique le retrouve, avec son nom.
        $archived = StockItem::withTrashed()->findOrFail($item->id);
        $this->assertTrue($archived->trashed());
        $this->assertStringStartsWith('Farine', $archived->movements()->first()->stockItem->name);

        // Et le nom est libre pour un nouvel article.
        $this->actingAs($admin)->post(route('stock-items.store'), [
            'name' => 'Farine',
            'unit' => 'kg',
            'quantity' => 0,
            'alert_threshold' => 1,
            'unit_cost' => 50,
        ])->assertRedirect(route('stock-items.index'));

        $this->assertEquals(1, StockItem::where('name', 'Farine')->count());
    }

    public function test_an_item_still_in_a_recipe_is_refused_with_the_product_names(): void
    {
        $admin = User::factory()->admin()->create();
        $item = StockItem::factory()->create(['name' => 'Pain arabe']);
        $category = ProductCategory::factory()->create();
        $kebab = Product::factory()->create(['name' => 'Kebab poulet', 'product_category_id' => $category->id]);
        $kebab->stockItems()->attach($item->id, ['quantity_needed' => 0.1]);

        $this->actingAs($admin)
            ->from(route('stock-items.index'))
            ->delete(route('stock-items.destroy', $item))
            ->assertRedirect(route('stock-items.index'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'Kebab poulet'));

        $this->assertNotNull(StockItem::find($item->id));
    }

    public function test_the_stock_list_offers_a_delete_button(): void
    {
        $admin = User::factory()->admin()->create();
        $item = StockItem::factory()->create(['name' => 'Sucre']);

        $this->actingAs($admin)->get(route('stock-items.index'))
            ->assertOk()
            ->assertSee(route('stock-items.destroy', $item), false);
    }

    public function test_archiving_a_product_returns_to_the_list_instead_of_a_404(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['name' => 'Ancien plat', 'product_category_id' => $category->id]);

        $this->actingAs($admin)
            ->from(route('products.edit', $product))
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('status');

        $this->actingAs($admin)->get(route('products.index'))->assertOk()
            ->assertDontSee(route('products.edit', $product), false);
    }
}
