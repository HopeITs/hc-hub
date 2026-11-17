<?php

declare(strict_types=1);

namespace Modules\Tracking\Policies;

use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\User;
use Modules\Tracking\Models\Project;

/**
 * A project's team (roadmap 2.2, D4). Whoever may see the project sees its
 * team; whoever manages the project (projects.update holders, its owner and
 * its program's manager, see ProjectPolicy::update()) adds and ends members.
 * A member is not given anything by being on the team.
 */
class ProjectMemberPolicy
{
    /**
     * Determine whether the user can see the project's team.
     */
    public function viewAny(User $user, Project $project): bool
    {
        return Gate::forUser($user)->allows('view', $project);
    }

    /**
     * Determine whether the user can add members to the project or end them.
     */
    public function manage(User $user, Project $project): bool
    {
        return Gate::forUser($user)->allows('update', $project);
    }
}
