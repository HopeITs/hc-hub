<?php

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Models\RecruitmentTag;
use Modules\Hr\Support\Recruitment\Access;

/**
 * The tag list itself. Creating, renaming and deleting a tag relabels every
 * application carrying it, across governorates, so it is one permission of
 * its own rather than part of tagging.
 */
class RecruitmentTagPolicy
{
    public function viewAny(User $user): bool
    {
        return Access::hasActivePermission($user, 'recruitment-applicants.view');
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user) && Access::hasActivePermission($user, 'recruitment-applicants.manage-tags');
    }

    public function update(User $user, RecruitmentTag $tag): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, RecruitmentTag $tag): bool
    {
        return $this->create($user);
    }

    public function restore(User $user, RecruitmentTag $tag): bool
    {
        return $this->create($user);
    }
}
