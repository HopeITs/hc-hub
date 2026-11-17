<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\ProjectMembers;

use App\Notifications\EmployeeRecipients;
use App\Support\EmployeeLookup;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Tracking\Enums\ProjectMemberRole;
use Modules\Tracking\Enums\ProjectMemberSource;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\ProjectMember;

/**
 * Add someone to a project's team (roadmap 2.2, D4). The member must be an
 * active employee; the role is any but Owner, which follows the project's
 * owner. One open row per person and role: the unique index settles two
 * managers acting at once. The start date is the one given, or unknown.
 * Error keys match the team dialog's fields.
 */
class AddProjectMember
{
    /**
     * @throws ValidationException when the member, role or date is refused
     */
    public function handle(Project $project, int $employeeId, string $role, ?string $startsOn = null): ProjectMember
    {
        Gate::authorize('manage', [ProjectMember::class, $project]);

        $startsOn = blank($startsOn) ? null : $startsOn;

        Validator::make(['role' => $role, 'starts_on' => $startsOn], [
            'role' => ['required', Rule::in(array_map(fn (ProjectMemberRole $case): string => $case->value, ProjectMemberRole::assignable()))],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
        ], [], [
            'role' => __('projects.team.role'),
            'starts_on' => __('projects.team.starts_on'),
        ])->validate();

        if (app(EmployeeLookup::class)->activeIds([$employeeId]) === []) {
            throw ValidationException::withMessages(['employee_id' => __('projects.team.choose_an_active_employee')]);
        }

        $member = DB::transaction(function () use ($project, $employeeId, $role, $startsOn): ProjectMember {
            $this->refuseOpenRow($project, $employeeId, $role, lock: true);

            try {
                return ProjectMember::query()->create([
                    'project_id' => $project->id,
                    'employee_id' => $employeeId,
                    'role' => $role,
                    'starts_on' => $startsOn,
                    'added_by' => Auth::id(),
                    'source' => ProjectMemberSource::Manual->value,
                ]);
            } catch (UniqueConstraintViolationException) {
                // Another manager added the same person in the same role after we looked.
                throw ValidationException::withMessages(['employee_id' => __('projects.team.already_member')]);
            }
        });

        $project->recordActivity('project.member_added', properties: [
            'employee_id' => $employeeId,
            'employee' => app(EmployeeRecipients::class)->names([$employeeId])[$employeeId] ?? null,
            'member_role' => $role,
        ]);

        return $member;
    }

    private function refuseOpenRow(Project $project, int $employeeId, string $role, bool $lock): void
    {
        $exists = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('employee_id', $employeeId)
            ->where('role', $role)
            ->current()
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['employee_id' => __('projects.team.already_member')]);
        }
    }
}
