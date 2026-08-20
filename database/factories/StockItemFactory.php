<?php

namespace Database\Factories;

use App\Enums\Unit;
use App\Models\StockItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockItem>
 */
class StockItemFactory extends Factory
{
    protected $model = StockItem::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'unit' => fake()->randomElement(Unit::cases()),
            'quantity' => fake()->numberBetween(0, 100),
            'alert_threshold' => fake()->numberBetween(1, 10),
            'unit_cost' => fake()->numberBetween(10, 500),
        ];
    }

    /** Article passé sous son seuil d'alerte. */
    public function low(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 1,
            'alert_threshold' => 5,
        ]);
    }
}
