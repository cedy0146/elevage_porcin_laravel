<?php

namespace Database\Factories;

use App\Models\Farm;
use App\Models\Pen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pen>
 */
class PenFactory extends Factory
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
            'code' => 'P-'.fake()->unique()->numberBetween(1, 999),
            'name' => 'Loge '.fake()->randomLetter(),
            'capacity' => fake()->numberBetween(20, 120),
            'type' => fake()->randomElement(['fattening', 'maternity', 'quarantine']),
            'status' => 'free',
        ];
    }

    public function occupied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'occupied',
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'maintenance',
        ]);
    }
}
