<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Enums\ResultType;
use Modules\Tracking\Models\ActivityPlan;
use Modules\Tracking\Models\ActivityPlanItem;
use Modules\Tracking\Models\Result;

/**
 * @extends Factory<ActivityPlanItem>
 */
class ActivityPlanItemFactory extends Factory
{
    /**
     * A session activity of a new plan, on a new logframe activity.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_plan_id' => ActivityPlan::factory(),
            'result_id' => fn (): int => Result::factory()->activity(Result::factory()->type(ResultType::Output)->create())->create()->id,
        ];
    }
}
