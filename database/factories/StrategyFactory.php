<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Models\Strategy;

/**
 * @extends Factory<Strategy>
 */
class StrategyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = fake()->numberBetween(2016, 2026);

        return [
            'name' => fake()->country().' Strategy',
            'start_year' => $startYear,
            'end_year' => fn (array $attributes): int => (int) $attributes['start_year'] + 4,
        ];
    }
}
