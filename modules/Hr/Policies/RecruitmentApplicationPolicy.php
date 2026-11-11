<?php

namespace Modules\Hr\Policies;

use Illuminate\Auth\Access\Response;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentScopeResolver;
use Modules\Hr\Enums\RecruitmentScopeType;
use Modules\Hr\Models\RecruitmentApplication;
use Modules\Hr\Support\Recruitment\Access;

class RecruitmentApplicationPolicy
{
    public function __construct(private readonly RecruitmentScopeResolver $scopes) {}

    public function view(User $user, RecruitmentApplication $application): bool
    {
        return (Access::hasActivePermission($user, 'recruitment-applicants.view')
                && $this->scopes->canAccessApplication($user, $application))
            || $application->applicant?->currentReviewParticipants()->where('user_id', $user->getKey())->exists();
    }

    /** Assigning access remains an HR action inside the application's normal scope. */
    public function manageReviewParticipants(User $user, RecruitmentApplication $application): bool
    {
        return Access::hasActivePermission($user, 'recruitment-applicants.manage')
            && $this->scopes->canAccessApplication($user, $application);
    }

    public function bulkManageReviewParticipants(User $user): bool
    {
        return Access::hasActivePermission($user, 'recruitment-applicants.manage')
            && Access::hasActivePermission($user, 'recruitment-applicants.view');
    }

    /** Its own permission: reading an application never implies deciding it. */
    public function changeStatus(User $user, RecruitmentApplication $application): bool
    {
        return Access::hasActivePermission($user, 'recruitment-applicants.change-status')
            && $this->scopes->canAccessApplication($user, $application);
    }

    /** Accepting is a hiring decision: its own grant, on top of change-status. */
    public function accept(User $user, RecruitmentApplication $application): bool
    {
        return Access::hasActivePermission($user, 'recruitment-applicants.accept')
            && $this->changeStatus($user, $application);
    }

    /**
     * Rejecting many at once, which emails every one of them. The records are
     * re-scoped one by one in RecruitmentApplicationStatusService, so this
     * answers only "may they bulk-reject at all".
     */
    public function bulkReject(User $user): bool
    {
        return Access::hasActivePermission($user, 'recruitment-applicants.view')
            && Access::hasActivePermission($user, 'recruitment-applicants.change-status')
            && Access::hasActivePermission($user, 'recruitment-applicants.bulk-reject');
    }

    /**
     * Taking candidate data out of the system as a file.
     *
     * The permission is read with hasExplicitPermission(): an administrator
     * role is not assumed to hold it, and no role is given it by default (see
     * never_granted_to_roles). It needs 'view' as well — an export is a list
     * the caller could already open — and some scope to export from. Which
     * rows come out is decided by the scoped query in the exporter, never here.
     *
     * Each denial carries a code so the export audit says why, not just that.
     */
    public function export(User $user): Response
    {
        if (! Access::hasExplicitPermission($user, 'recruitment-applicants.export')
            || ! Access::hasActivePermission($user, 'recruitment-applicants.view')) {
            return Response::deny(code: 'missing_permission');
        }

        if ($this->scopes->scopeType($user) === RecruitmentScopeType::None->value) {
            return Response::deny(code: 'no_scope');
        }

        return Response::allow();
    }

    /** Tagging one application: its own permission, and the application must be in scope. */
    public function tag(User $user, RecruitmentApplication $application): bool
    {
        return Access::hasActivePermission($user, 'recruitment-applicants.tag')
            && $this->scopes->canAccessApplication($user, $application);
    }

    /**
     * Tagging many at once. A separate grant because one click reaches every
     * selected record; the records themselves are re-scoped one by one in
     * RecruitmentTagService, so this answers only "may they bulk-tag at all".
     */
    public function bulkTag(User $user): bool
    {
        return Access::hasActivePermission($user, 'recruitment-applicants.view')
            && Access::hasActivePermission($user, 'recruitment-applicants.bulk-tag');
    }
}
