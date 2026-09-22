<?php

namespace App\Policies;

use App\Models\RecruitmentApplication;
use App\Models\User;
use App\Services\RecruitmentScopeResolver;

class RecruitmentApplicationPolicy
{
    public function __construct(private readonly RecruitmentScopeResolver $scopes) {}

    public function viewAny(User $user): bool
    {
        return $user->hasActivePermission('recruitment-applications.view');
    }

    public function view(User $user, RecruitmentApplication $application): bool
    {
        return $user->hasActivePermission('recruitment-applications.view')
            && $this->scopes->canAccessApplication($user, $application);
    }

    public function manage(User $user, RecruitmentApplication $application): bool
    {
        return $user->hasActivePermission('recruitment-applications.manage')
            && $this->scopes->canAccessApplication($user, $application);
    }

    public function update(User $user, RecruitmentApplication $application): bool
    {
        return $this->manage($user, $application);
    }

    /** Moving the status machine (shortlist, reject, select, ...) is a decision, not an edit. */
    public function decide(User $user, RecruitmentApplication $application): bool
    {
        return $user->hasActivePermission('recruitment-applications.decide')
            && $this->scopes->canAccessApplication($user, $application);
    }

    public function tag(User $user, RecruitmentApplication $application): bool
    {
        return $this->manage($user, $application);
    }

    public function note(User $user, RecruitmentApplication $application): bool
    {
        return $this->manage($user, $application);
    }
}
