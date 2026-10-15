<?php

namespace Database\Factories;

use App\Models\Farm;
use App\Models\PigLot;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $headCount = fake()->numberBetween(5, 40);
        $totalWeight = round($headCount * fake()->randomFloat(2, 70, 110), 2);
        $pricePerKg = fake()->randomFloat(2, 8000, 12000);

        return [
            'farm_id' => Farm::factory(),
            'pig_lot_id' => PigLot::factory(),
            'user_id' => User::factory(),
            'customer_name' => fake()->randomElement(['Trader Antsirabe', 'Socopa', 'Boucherie Isoraka']),
            'head_count' => $headCount,
            'total_weight' => $totalWeight,
            'price_per_kg' => $pricePerKg,
            'amount' => round($totalWeight * $pricePerKg, 2),
            'sold_on' => now()->toDateString(),
        ];
    }
}
