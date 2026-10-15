<?php

namespace Database\Factories;

use App\Models\Breeder;
use App\Models\Farm;
use App\Models\Farrowing;
use App\Models\Mating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Farrowing>
 */
class FarrowingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $farrownOn = fake()->dateTimeBetween('-60 days', '-5 days');

        return [
            'farm_id' => Farm::factory(),
            'mating_id' => Mating::factory(),
            'sow_id' => Breeder::factory()->sow(),
            'user_id' => User::factory(),
            'farrowed_on' => $farrownOn,
            'born_alive' => fake()->numberBetween(6, 14),
            'stillborn' => fake()->numberBetween(0, 2),
            'weaned_count' => null,
            'weaned_on' => null,
            'pig_lot_id' => null,
        ];
    }
}
