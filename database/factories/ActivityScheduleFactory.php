<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Enums\ResultType;
use Modules\Tracking\Models\ActivitySchedule;
use Modules\Tracking\Models\Result;

/**
 * @extends Factory<ActivitySchedule>
 */
class ActivityScheduleFactory extends Factory
{
    /**
     * Define the model's default state: a whole month on the proposal
     * calendar of a new activity (under a new output, as an activity needs).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'result_id' => fn (): int => Result::factory()->activity(Result::factory()->type(ResultType::Output)->create())->create()->id,
            'month' => fake()->dateTimeBetween('-1 year', '+1 year')->format('Y-m-01'),
        ];
    }
}
