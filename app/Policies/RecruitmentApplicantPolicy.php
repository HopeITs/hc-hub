<?php

namespace App\Policies;

use App\Models\RecruitmentApplicant;
use App\Models\User;
use App\Services\RecruitmentScopeResolver;

class RecruitmentApplicantPolicy
{
    public function __construct(private readonly RecruitmentScopeResolver $scopes) {}

    public function viewAny(User $user): bool
    {
        return $user->hasActivePermission('recruitment-applicants.view');
    }

    public function view(User $user, RecruitmentApplicant $applicant): bool
    {
        return $this->viewAny($user) && $this->scopes->canAccessApplicant($user, $applicant);
    }
}
