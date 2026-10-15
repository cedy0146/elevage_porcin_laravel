<?php

namespace Database\Factories;

use App\Models\Farm;
use App\Models\Pen;
use App\Models\PigLot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PigLot>
 */
class PigLotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $arrivedOn = fake()->dateTimeBetween('-90 days', '-10 days');

        return [
            'farm_id' => Farm::factory(),
            'pen_id' => Pen::factory(),
            'code' => 'L-2026-'.fake()->unique()->numberBetween(1, 999),
            'source' => fake()->randomElement(['purchase', 'birth']),
            'initial_count' => fake()->numberBetween(20, 80),
            'initial_average_weight' => fake()->randomFloat(2, 8, 25),
            'arrived_on' => $arrivedOn,
            'expected_end_on' => fake()->dateTimeBetween('+30 days', '+90 days'),
            'closed_on' => null,
            'status' => 'active',
            'notes' => fake()->boolean(30) ? fake()->sentence() : null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'closed_on' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'closed_on' => now()->toDateString(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'closed_on' => now()->toDateString(),
        ]);
    }
}
