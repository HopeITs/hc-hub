<?php

namespace Database\Factories;

use Database\Factories\Concerns\CreatesHrRecords;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\Program;
use Modules\Tracking\Models\Strategy;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    use CreatesHrRecords;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['WASH', 'Shelter', 'Protection', 'Livelihoods', 'Health']),
            'code' => fake()->bothify('??####'),
            'slug' => fake()->unique()->slug(),
            'strategy_id' => Strategy::factory(),
            'manager_id' => fn (): int => $this->createHrEmployee(),
        ];
    }

    /**
     * Indicate a program nobody has linked to a strategy or manager yet.
     */
    public function unassigned(): static
    {
        return $this->state([
            'strategy_id' => null,
            'manager_id' => null,
        ]);
    }
}
