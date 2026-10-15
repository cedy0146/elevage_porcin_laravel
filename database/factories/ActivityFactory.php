<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Farm;
use App\Models\PigLot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
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
            'pig_lot_id' => fake()->boolean(70) ? PigLot::factory() : null,
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['cleaning', 'maintenance', 'transfer', 'treatment', 'other']),
            'performed_on' => now()->toDateString(),
            'description' => fake()->sentence(),
            'cost' => fake()->randomFloat(2, 0, 50000),
        ];
    }
}
