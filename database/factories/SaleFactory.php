<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        $total = fake()->numberBetween(50, 1500);

        return [
            'sale_number' => 'V-'.fake()->unique()->numerify('########'),
            'user_id' => User::factory(),
            'cash_session_id' => null,
            'sold_at' => now(),
            'subtotal' => $total,
            'total' => $total,
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
        ];
    }
}
