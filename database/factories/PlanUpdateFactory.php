<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Models\ActivityPlan;
use Modules\Tracking\Models\PlanUpdate;
use Modules\Tracking\Models\Project;

/**
 * @extends Factory<PlanUpdate>
 */
class PlanUpdateFactory extends Factory
{
    /**
     * A draft update of an approved plan for a new centre.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_plan_id' => ActivityPlan::factory()->approved(),
            'project_id' => Project::factory(),
            'month' => '2027-01-01',
        ];
    }
}
