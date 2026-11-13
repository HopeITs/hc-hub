<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Enums\FollowUpTrigger;
use Modules\Tracking\Models\ActivityPlanFollowUp;
use Modules\Tracking\Models\ActivityPlanItem;

/**
 * @extends Factory<ActivityPlanFollowUp>
 */
class ActivityPlanFollowUpFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_plan_item_id' => ActivityPlanItem::factory(),
            'activity_plan_id' => fn (array $attributes): int => (int) ActivityPlanItem::query()->whereKey($attributes['activity_plan_item_id'])->value('activity_plan_id'),
            'trigger' => FollowUpTrigger::Finish->value,
            'description' => 'Run the post test',
        ];
    }
}
