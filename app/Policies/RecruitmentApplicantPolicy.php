<?php

namespace App\Policies;

use App\Models\RecruitmentApplicant;
use App\Models\User;
use App\Services\RecruitmentScopeResolver;

/**
 * Applicant PII (RecruitmentApplicant is the person, not a single submission)
 * is only ever reached through an application the user can already see — see
 * RecruitmentScopeResolver::scopeApplicants().
 */
class RecruitmentApplicantPolicy
{
    public function __construct(private readonly RecruitmentScopeResolver $scopes) {}

    public function viewAny(User $user): bool
    {
        return $user->hasActivePermission('recruitment-applicants.view');
    }

    public function view(User $user, RecruitmentApplicant $applicant): bool
    {
        return $user->hasActivePermission('recruitment-applicants.view')
            && $this->scopes->canAccessApplicant($user, $applicant);
    }

    public function manage(User $user, RecruitmentApplicant $applicant): bool
    {
        return $user->hasActivePermission('recruitment-applicants.manage')
            && $this->scopes->canAccessApplicant($user, $applicant);
    }

    public function update(User $user, RecruitmentApplicant $applicant): bool
    {
        return $this->manage($user, $applicant);
    }

    public function note(User $user, RecruitmentApplicant $applicant): bool
    {
        return $this->manage($user, $applicant);
    }
}
