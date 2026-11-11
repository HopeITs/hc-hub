<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Models\Milestone;
use Modules\Tracking\Models\ScheduleDependency;

/**
 * @extends Factory<ScheduleDependency>
 */
class ScheduleDependencyFactory extends Factory
{
    /**
     * A finish-to-start link between two milestones of one project. `type` and
     * `lag_days` are left to their column defaults.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => fn (): int => Milestone::factory()->create()->project_id,
            'predecessor_type' => 'milestone',
            'predecessor_id' => fn (array $attributes): int => Milestone::factory()->create(['project_id' => $attributes['project_id']])->id,
            'successor_type' => 'milestone',
            'successor_id' => fn (array $attributes): int => Milestone::factory()->create(['project_id' => $attributes['project_id']])->id,
        ];
    }
}
