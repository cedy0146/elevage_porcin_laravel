<?php

namespace Database\Factories;

use App\Models\Farm;
use App\Models\PigLot;
use App\Models\User;
use App\Models\Weighing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Weighing>
 */
class WeighingFactory extends Factory
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
            'pig_lot_id' => PigLot::factory(),
            'user_id' => User::factory(),
            'weighed_on' => now()->toDateString(),
            'average_weight' => fake()->randomFloat(2, 25, 110),
            'sample_size' => fake()->numberBetween(5, 30),
        ];
    }
}
