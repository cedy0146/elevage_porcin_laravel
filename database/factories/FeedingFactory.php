<?php

namespace Database\Factories;

use App\Models\Farm;
use App\Models\Feeding;
use App\Models\FeedType;
use App\Models\PigLot;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feeding>
 */
class FeedingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 5, 40);
        $unitCost = fake()->randomFloat(2, 2500, 3500);

        return [
            'farm_id' => Farm::factory(),
            'pig_lot_id' => PigLot::factory(),
            'feed_type_id' => FeedType::factory(),
            'user_id' => User::factory(),
            'stock_movement_id' => StockMovement::factory(),
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => round($quantity * $unitCost, 2),
            'fed_on' => now()->toDateString(),
            'notes' => null,
        ];
    }
}
