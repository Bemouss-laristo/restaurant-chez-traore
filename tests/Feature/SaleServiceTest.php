<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\StockMovementReason;
use App\Models\CashSession;
use App\Models\Product;
use App\Models\StockItem;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): SaleService
    {
        return app(SaleService::class);
    }

    public function test_it_records_a_sale_with_snapshot_price_and_total(): void
    {
        $user = User::factory()->caissier()->create();
        $product = Product::factory()->create(['sale_price' => 300]);

        $sale = $this->service()->record(
            $user,
            [['product_id' => $product->id, 'quantity' => 2]],
            PaymentMethod::Especes,
            null,
        );

        $this->assertEquals(600.0, (float) $sale->total);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 300.00,
            'line_total' => 600.00,
        ]);
    }

    public function test_it_decrements_stock_using_the_recipe(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['sale_price' => 300]);
        $ingredient = StockItem::factory()->create(['quantity' => 10]);
        $product->stockItems()->attach($ingredient->id, ['quantity_needed' => 0.5]);

        $this->service()->record(
            $user,
            [['product_id' => $product->id, 'quantity' => 2]],
            PaymentMethod::Especes,
            null,
        );

        // 2 unités vendues × 0,5 = 1 retiré → 10 - 1 = 9
        $this->assertEquals(9.0, (float) $ingredient->fresh()->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $ingredient->id,
            'reason' => StockMovementReason::Sale->value,
        ]);
    }

    public function test_a_product_without_recipe_does_not_touch_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->service()->record(
            $user,
            [['product_id' => $product->id, 'quantity' => 3]],
            PaymentMethod::Bankily,
            null,
        );

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_it_attaches_the_sale_to_the_open_cash_session(): void
    {
        $user = User::factory()->create();
        $session = CashSession::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['sale_price' => 100]);

        $sale = $this->service()->record(
            $user,
            [['product_id' => $product->id, 'quantity' => 1]],
            PaymentMethod::Especes,
            $session,
        );

        $this->assertEquals($session->id, $sale->cash_session_id);
    }
}
