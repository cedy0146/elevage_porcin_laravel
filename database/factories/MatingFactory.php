<?php

namespace Database\Factories;

use App\Models\Breeder;
use App\Models\Farm;
use App\Models\Mating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Mating>
 */
class MatingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $matedOn = fake()->dateTimeBetween('-120 days', '-10 days');

        return [
            'farm_id' => Farm::factory(),
            'sow_id' => Breeder::factory()->sow(),
            'boar_id' => Breeder::factory()->boar(),
            'user_id' => User::factory(),
            'mated_on' => $matedOn,
            'expected_farrowing_on' => Carbon::parse($matedOn)->addDays(114)->toDateString(),
            'status' => 'pending',
        ];
    }
}
