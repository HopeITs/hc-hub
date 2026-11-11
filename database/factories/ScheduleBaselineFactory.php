<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\ScheduleBaseline;

/**
 * @extends Factory<ScheduleBaseline>
 */
class ScheduleBaselineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->randomElement(['Approved plan', 'Inception baseline', 'Revised plan']),
        ];
    }
}
