<?php

namespace Database\Factories;

use App\Models\CashSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashSession>
 */
class CashSessionFactory extends Factory
{
    protected $model = CashSession::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'opened_at' => now(),
            'closed_at' => null,
            'opening_float' => fake()->numberBetween(0, 5000),
            'expected_cash' => null,
            'counted_cash' => null,
            'difference' => null,
            'status' => CashSession::STATUS_OPEN,
            'note' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'closed_at' => now(),
            'expected_cash' => 10000,
            'counted_cash' => 10000,
            'difference' => 0,
            'status' => CashSession::STATUS_CLOSED,
        ]);
    }
}
