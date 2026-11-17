<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Hr\Models\Employee;
use Modules\Tracking\Enums\ProjectMemberRole;
use Modules\Tracking\Enums\ProjectMemberSource;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\ProjectMember;

/**
 * @extends Factory<ProjectMember>
 */
class ProjectMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Only the NOT NULL columns without a database default (see
     * .ai/rules/seeders.md).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'employee_id' => Employee::factory(),
            'role' => ProjectMemberRole::Member->value,
            'source' => ProjectMemberSource::Manual->value,
        ];
    }

    /**
     * A membership that ended on the given day.
     */
    public function endedOn(string $date): static
    {
        return $this->state(['ends_on' => $date]);
    }
}
