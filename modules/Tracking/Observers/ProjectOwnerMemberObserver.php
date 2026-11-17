<?php

declare(strict_types=1);

namespace Modules\Tracking\Observers;

use Illuminate\Support\Facades\Auth;
use Modules\Tracking\Enums\ProjectMemberRole;
use Modules\Tracking\Enums\ProjectMemberSource;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\ProjectMember;

/**
 * Keeps the team's Owner row in step with projects.owner_employee_id (D4:
 * the project's owner column stays the source). A new project, or a new
 * owner, gets an open Owner row starting today; the previous owner's row
 * ends today. Both dates are the day the change happened, so none is
 * invented. A raw query-builder write fires no event and is not followed.
 */
class ProjectOwnerMemberObserver
{
    public function saved(Project $project): void
    {
        if (! $project->wasRecentlyCreated && ! $project->wasChanged('owner_employee_id')) {
            return;
        }

        $ownerId = $project->getAttribute('owner_employee_id');
        $today = now()->toDateString();

        ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('role', ProjectMemberRole::Owner->value)
            ->current()
            ->when($ownerId !== null, fn ($query) => $query->where('employee_id', '!=', $ownerId))
            ->get()
            ->each(fn (ProjectMember $row) => $row->forceFill(['ends_on' => $today, 'ended_by' => Auth::id()])->save());

        if ($ownerId === null) {
            return;
        }

        $alreadyOpen = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('employee_id', (int) $ownerId)
            ->where('role', ProjectMemberRole::Owner->value)
            ->current()
            ->exists();

        if (! $alreadyOpen) {
            ProjectMember::query()->create([
                'project_id' => $project->id,
                'employee_id' => (int) $ownerId,
                'role' => ProjectMemberRole::Owner->value,
                'starts_on' => $today,
                'added_by' => Auth::id(),
                'source' => ProjectMemberSource::Manual->value,
            ]);
        }
    }
}
