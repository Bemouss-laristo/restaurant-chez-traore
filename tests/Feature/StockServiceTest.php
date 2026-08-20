<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Models\StockItem;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): StockService
    {
        return app(StockService::class);
    }

    public function test_add_stock_increases_quantity_and_logs_movement(): void
    {
        $item = StockItem::factory()->create(['quantity' => 10]);

        $this->service()->addStock($item, 5, StockMovementReason::Purchase);

        $this->assertEquals(15.0, (float) $item->fresh()->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'stock_item_id' => $item->id,
            'type' => StockMovementType::In->value,
            'reason' => StockMovementReason::Purchase->value,
        ]);
    }

    public function test_remove_stock_decreases_quantity(): void
    {
        $item = StockItem::factory()->create(['quantity' => 10]);

        $this->service()->removeStock($item, 4, StockMovementReason::Waste);

        $this->assertEquals(6.0, (float) $item->fresh()->quantity);
    }

    public function test_remove_stock_never_goes_below_zero(): void
    {
        $item = StockItem::factory()->create(['quantity' => 3]);

        $this->service()->removeStock($item, 10, StockMovementReason::Waste);

        $this->assertEquals(0.0, (float) $item->fresh()->quantity);
    }

    public function test_adjust_sets_counted_quantity_and_records_delta(): void
    {
        $item = StockItem::factory()->create(['quantity' => 10]);

        $movement = $this->service()->adjust($item, 7);

        $this->assertEquals(7.0, (float) $item->fresh()->quantity);
        $this->assertEquals(3.0, (float) $movement->quantity);
        $this->assertEquals(StockMovementType::Out, $movement->type);
        $this->assertEquals(StockMovementReason::Manual, $movement->reason);
    }
}
