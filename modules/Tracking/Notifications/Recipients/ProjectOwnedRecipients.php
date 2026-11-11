<?php

declare(strict_types=1);

namespace Modules\Tracking\Notifications\Recipients;

use App\Models\Activity;
use App\Notifications\Recipients\ModuleRecipients;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Tracking\Models\Project;

/**
 * Records hanging off a project (risks, indicators, targets): the project
 * owner is direct.
 */
abstract class ProjectOwnedRecipients extends ModuleRecipients
{
    public function directEmployeeIds(Activity $activity): array
    {
        $class = Relation::getMorphedModel($activity->subject_type);
        $projectId = $class === null ? null : $class::query()->whereKey($activity->subject_id)->value('project_id');

        return $this->uniqueIds([
            Project::ownerEmployeeIdFor($projectId),
            ...$this->idsFromProperty($activity, 'employee_ids'),
        ]);
    }
}
