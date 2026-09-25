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

    /** Accepting is a hiring decision: its own grant, on top of change-status. */
    public function accept(User $user, RecruitmentApplication $application): bool
    {
        return $user->hasActivePermission('recruitment-applicants.accept')
            && $this->changeStatus($user, $application);
    }

    /**
     * Rejecting many at once, which emails every one of them. The records are
     * re-scoped one by one in RecruitmentApplicationStatusService, so this
     * answers only "may they bulk-reject at all".
     */
    public function bulkReject(User $user): bool
    {
        return $user->hasActivePermission('recruitment-applicants.view')
            && $user->hasActivePermission('recruitment-applicants.change-status')
            && $user->hasActivePermission('recruitment-applicants.bulk-reject');
    }

    /** Tagging one application: its own permission, and the application must be in scope. */
    public function tag(User $user, RecruitmentApplication $application): bool
    {
        return $user->hasActivePermission('recruitment-applicants.tag')
            && $this->view($user, $application);
    }

    /**
     * Tagging many at once. A separate grant because one click reaches every
     * selected record; the records themselves are re-scoped one by one in
     * RecruitmentTagService, so this answers only "may they bulk-tag at all".
     */
    public function bulkTag(User $user): bool
    {
        return $user->hasActivePermission('recruitment-applicants.view')
            && $user->hasActivePermission('recruitment-applicants.bulk-tag');
    }
}
