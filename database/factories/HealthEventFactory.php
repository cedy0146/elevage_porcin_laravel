<?php

namespace Database\Factories;

use App\Models\Farm;
use App\Models\HealthEvent;
use App\Models\PigLot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthEvent>
 */
class HealthEventFactory extends Factory
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
            'vaccine_schedule_id' => null,
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['vaccination', 'treatment', 'observation']),
            'name' => 'Acte sanitaire '.fake()->unique()->numberBetween(1, 999),
            'scheduled_on' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
            'done_on' => null,
            'status' => 'todo',
            'cost' => 0,
            'overdue_notified_at' => null,
            'notes' => fake()->boolean(30) ? fake()->sentence() : null,
        ];
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'done',
            'done_on' => now()->toDateString(),
            'user_id' => User::factory(),
        ]);
    }
}
