<?php

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'cash_session_id' => null,
            'expense_category' => fake()->randomElement(ExpenseCategory::cases()),
            'amount' => fake()->numberBetween(100, 10000),
            'description' => fake()->optional()->sentence(),
            'spent_at' => now(),
            'payment_method' => PaymentMethod::Especes,
        ];
    }
}
