<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Enums\ActivityStatus;
use Modules\Tracking\Enums\ResultType;
use Modules\Tracking\Models\MonthlyReport;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\ProjectActivityStatus;
use Modules\Tracking\Models\Result;

/**
 * @extends Factory<ProjectActivityStatus>
 */
class ProjectActivityStatusFactory extends Factory
{
    /**
     * An activity confirmed as started by a validated report of the same
     * project.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'result_id' => fn (): int => Result::factory()->activity(Result::factory()->type(ResultType::Output)->create())->create()->id,
            'status' => ActivityStatus::Started,
            'monthly_report_id' => fn (array $attributes): int => MonthlyReport::factory()->validated()->create(['project_id' => $attributes['project_id']])->id,
        ];
    }
}
