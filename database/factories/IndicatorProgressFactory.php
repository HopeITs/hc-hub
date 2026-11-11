<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\User;
use Modules\Tracking\Models\Indicator;
use Modules\Tracking\Models\IndicatorProgress;

/**
 * @extends Factory<IndicatorProgress>
 */
class IndicatorProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'indicator_id' => Indicator::factory(),
            'date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'value' => fake()->randomFloat(2, 0, 25000),
            'notes' => fake()->sentence(),
            'source_of_verification' => fake()->randomElement([
                'Distribution list',
                'Attendance sheet',
                'Household survey',
                'Field monitoring visit',
                'Partner report',
            ]),
            'recorded_by' => User::factory(),
        ];
    }
}
