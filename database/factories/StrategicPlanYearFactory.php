<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Models\StrategicPlan;
use Modules\Tracking\Models\StrategicPlanYear;

/**
 * @extends Factory<StrategicPlanYear>
 */
class StrategicPlanYearFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'strategic_plan_id' => StrategicPlan::factory(),
            'year' => (int) now()->year,
            'goals' => fake()->sentence(),
        ];
    }
}
