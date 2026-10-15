<?php

namespace Database\Factories;

use App\Models\Farm;
use App\Models\FeedType;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 10, 200);

        return [
            'farm_id' => Farm::factory(),
            'feed_type_id' => FeedType::factory(),
            'pig_lot_id' => null,
            'user_id' => User::factory(),
            'type' => 'initial',
            'quantity' => $quantity,
            'quantity_before' => 0,
            'quantity_after' => $quantity,
            'unit_cost' => fake()->randomFloat(2, 2500, 3500),
            'reason' => 'Stock initial',
            'moved_at' => now(),
        ];
    }
}
