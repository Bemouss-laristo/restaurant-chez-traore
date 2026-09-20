<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les listes partent entières dans la page : c'est ce qui permet à la recherche
 * de filtrer à la frappe, sans aller-retour réseau. Ces tests vérifient que rien
 * n'est tronqué par une pagination oubliée.
 */
class LiveSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_stock_list_sends_every_item_to_the_page(): void
    {
        $admin = User::factory()->admin()->create();

        for ($i = 1; $i <= 20; $i++) {
            StockItem::factory()->create(['name' => 'Article '.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
        }

        $response = $this->actingAs($admin)->get(route('stock-items.index'))->assertOk();

        // Le 20e article était hors de l'ancienne première page de 15.
        $response->assertSee('Article 20')->assertSee('liveSearch(', false);
    }

    public function test_the_product_list_sends_every_product_to_the_page(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ProductCategory::factory()->create(['name' => 'Snacks']);

        for ($i = 1; $i <= 20; $i++) {
            Product::factory()->create([
                'name' => 'Produit '.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'product_category_id' => $category->id,
            ]);
        }

        $this->actingAs($admin)->get(route('products.index'))
            ->assertOk()
            ->assertSee('Produit 20')          // au-delà des 12 anciennes cartes
            ->assertSee('liveSearch(', false);
    }

    public function test_the_staff_list_sends_every_user_to_the_page(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Aaa Admin']);
        User::factory()->count(20)->create();

        $last = User::orderBy('name')->get()->last();

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($last->name)
            ->assertSee('liveSearch(', false);
    }
}
