<?php

declare(strict_types=1);

namespace Modules\Tracking\Notifications\Recipients;

use App\Models\Activity;
use App\Notifications\Recipients\ModuleRecipients;
use Modules\Tracking\Models\Milestone;
use Modules\Tracking\Models\Project;

/**
 * Milestones: the responsible person and the project owner are direct;
 * Managers watch; postponed / cancelled is ★.
 *
 * A change of the responsible person is logged under `responsible_employee_id`;
 * activities from before the column rename (roadmap 1.9) carry it as
 * `responsible_user_id` and are read too.
 */
final class MilestoneRecipients extends ModuleRecipients
{
    public function directEmployeeIds(Activity $activity): array
    {
        $milestone = Milestone::query()->select(['id', 'project_id', 'responsible_employee_id'])->find($activity->subject_id);

        $ids = $milestone === null
            ? $this->idsFromProperty($activity, 'employee_ids')
            : [$milestone->responsible_employee_id, Project::ownerEmployeeIdFor($milestone->project_id)];

        return $this->uniqueIds([...$ids, $this->changedFrom($activity, 'responsible_employee_id') ?? $this->changedFrom($activity, 'responsible_user_id')]);
    }

    public function assignedEmployeeIds(Activity $activity): array
    {
        return $this->uniqueIds([match ($activity->event) {
            'milestone.responsible_changed' => $this->changedTo($activity, 'responsible_employee_id') ?? $this->changedTo($activity, 'responsible_user_id'),
            'milestone.created' => Milestone::query()->whereKey($activity->subject_id)->value('responsible_employee_id'),
            default => null,
        }]);
    }

    public function watcherRoles(Activity $activity): array
    {
        return ['Managers'];
    }

    public function isKeyEvent(Activity $activity): bool
    {
        return $activity->event === 'milestone.status_changed'
            && in_array($this->changedTo($activity, 'status'), ['postponed', 'cancelled'], true);
    }
}
