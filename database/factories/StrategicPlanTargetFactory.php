<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Models\Indicator;
use Modules\Tracking\Models\StrategicPlanTarget;
use Modules\Tracking\Models\StrategicPlanYear;

/**
 * @extends Factory<StrategicPlanTarget>
 */
class StrategicPlanTargetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'strategic_plan_year_id' => StrategicPlanYear::factory(),
            'indicator_id' => Indicator::factory(),
            'target' => fake()->numberBetween(10, 500),
        ];
    }
}
