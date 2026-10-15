<?php

namespace Database\Factories;

use App\Models\Breeder;
use App\Models\Farm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Breeder>
 */
class BreederFactory extends Factory
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
            'tag' => 'T-'.fake()->unique()->numberBetween(1, 9999),
            'sex' => fake()->randomElement(['sow', 'boar']),
            'breed' => fake()->randomElement(['Large White', 'Landrace', 'Pietrain', 'Duroc']),
            'born_on' => fake()->dateTimeBetween('-4 years', '-1 year'),
            'status' => 'active',
        ];
    }

    public function sow(): static
    {
        return $this->state(fn (array $attributes) => ['sex' => 'sow']);
    }

    public function boar(): static
    {
        return $this->state(fn (array $attributes) => ['sex' => 'boar']);
    }

    public function pregnant(): static
    {
        return $this->state(fn (array $attributes) => ['sex' => 'sow', 'status' => 'pregnant']);
    }
}
