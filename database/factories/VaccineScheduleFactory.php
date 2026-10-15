<?php

namespace Database\Factories;

use App\Models\Farm;
use App\Models\VaccineSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VaccineSchedule>
 */
class VaccineScheduleFactory extends Factory
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
            'name' => 'Vaccin '.fake()->unique()->numberBetween(1, 999),
            'target_age_days' => fake()->numberBetween(15, 60),
            'tolerance_days' => 2,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
