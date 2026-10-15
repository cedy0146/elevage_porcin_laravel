<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\Farm;
use App\Models\PigLot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'farm_id' => Farm::factory(),
            'pig_lot_id' => fake()->boolean(50) ? PigLot::factory() : null,
            'user_id' => User::factory(),
            'category' => fake()->randomElement(['feed', 'labor', 'veterinary', 'transport', 'energy', 'purchase', 'other']),
            'amount' => fake()->randomFloat(2, 10000, 500000),
            'spent_on' => now()->subDays(fake()->numberBetween(0, 60))->toDateString(),
            'description' => fake()->sentence(),
        ];
    }
}
