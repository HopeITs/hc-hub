<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\ProjectMembers;

use App\Notifications\EmployeeRecipients;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Tracking\Enums\ProjectMemberRole;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\ProjectMember;

/**
 * End someone's place on a project's team (roadmap 2.2, D4): ends_on and
 * ended_by are filled and the row stays, so the team's history is kept. The
 * end date is today unless one is given; it cannot be in the future or
 * before the membership started. The owner's row follows the project's owner
 * and is not ended here.
 */
class EndProjectMember
{
    /**
     * @return bool false when the membership had already ended
     *
     * @throws ValidationException when the date is refused or the row is the owner's
     */
    public function handle(ProjectMember $member, ?string $endsOn = null): bool
    {
        $project = Project::query()->findOrFail($member->project_id);

        Gate::authorize('manage', [ProjectMember::class, $project]);

        if ($member->role === ProjectMemberRole::Owner) {
            throw ValidationException::withMessages(['ends_on' => __('projects.team.owner_follows_project')]);
        }

        $endsOn = blank($endsOn) ? now()->toDateString() : $endsOn;

        $ended = DB::transaction(function () use ($member, $endsOn): bool {
            $row = ProjectMember::query()->lockForUpdate()->findOrFail($member->id);

            if (! $row->isCurrent()) {
                return false;
            }

            Validator::make(['ends_on' => $endsOn], [
                'ends_on' => array_values(array_filter([
                    'required',
                    'date_format:Y-m-d',
                    'before_or_equal:'.now()->toDateString(),
                    $row->starts_on === null ? null : 'after_or_equal:'.$row->starts_on->toDateString(),
                ])),
            ], [], ['ends_on' => __('projects.team.ends_on')])->validate();

            $row->forceFill(['ends_on' => $endsOn, 'ended_by' => Auth::id()])->save();

            return true;
        });

        if ($ended) {
            $employeeId = $member->employee_id;

            $project->recordActivity('project.member_ended', properties: [
                'employee_id' => $employeeId,
                'employee' => app(EmployeeRecipients::class)->names([$employeeId])[$employeeId] ?? null,
                'member_role' => $member->role->value,
                'ends_on' => $endsOn,
            ]);
        }

        return $ended;
    }
}
