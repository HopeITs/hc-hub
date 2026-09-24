<?php

namespace App\Policies;

use App\Models\RecruitmentApplication;
use App\Models\User;
use App\Services\RecruitmentScopeResolver;

class RecruitmentApplicationPolicy
{
    public function __construct(private readonly RecruitmentScopeResolver $scopes) {}

    public function view(User $user, RecruitmentApplication $application): bool
    {
        return $user->hasActivePermission('recruitment-applicants.view')
            && $this->scopes->canAccessApplication($user, $application);
    }

    /** Its own permission: reading an application never implies deciding it. */
    public function changeStatus(User $user, RecruitmentApplication $application): bool
    {
        return $user->hasActivePermission('recruitment-applicants.change-status')
            && $this->view($user, $application);
    }
}
