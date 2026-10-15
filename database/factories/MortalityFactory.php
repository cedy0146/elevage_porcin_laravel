<?php

namespace Database\Factories;

use App\Models\Farm;
use App\Models\Mortality;
use App\Models\PigLot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mortality>
 */
class MortalityFactory extends Factory
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
            'died_on' => now()->toDateString(),
            'count' => fake()->numberBetween(1, 3),
            'cause' => fake()->randomElement(['grippe', 'blessure', 'trouble digestif', 'inconnue']),
        ];
    }
}
