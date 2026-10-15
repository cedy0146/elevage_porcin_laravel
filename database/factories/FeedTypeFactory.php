<?php

namespace Database\Factories;

use App\Models\Farm;
use App\Models\FeedType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeedType>
 */
class FeedTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $feed = fake()->randomElement([
            ['name' => 'Aliment porcelet', 'stage' => 'piglet'],
            ['name' => 'Aliment croissance', 'stage' => 'grower'],
            ['name' => 'Aliment finition', 'stage' => 'finisher'],
            ['name' => 'Aliment truie', 'stage' => 'sow'],
        ]);

        return [
            'farm_id' => Farm::factory(),
            'name' => $feed['name'].' '.fake()->unique()->numberBetween(1, 999),
            'stage' => $feed['stage'],
            'unit' => 'kg',
            'unit_price' => fake()->randomFloat(2, 2500, 3500),
            'quantity' => fake()->randomFloat(2, 100, 800),
            'minimum_threshold' => 50,
        ];
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 10,
            'minimum_threshold' => 50,
        ]);
    }

    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
        ]);
    }
}
