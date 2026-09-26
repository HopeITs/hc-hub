<?php

namespace App\Policies;

use App\Models\RecruitmentApplication;
use App\Models\RecruitmentJobOffer;
use App\Models\User;
use App\Services\RecruitmentScopeResolver;

/**
 * Phase 2 Task 9.
 *
 * - view: view-offers, on an application in the user's recruitment scope.
 *   Status, dates and contract terms.
 * - viewSalary: the pay figure on top of that, only for users who may see
 *   salaries at all (User::canViewSalary() — active HR role, and never
 *   super-admin, which is salary-blind by design).
 * - create / manage: manage-offers, in scope, and canViewSalary(): writing an
 *   offer means entering the salary, so a user who may not see figures
 *   cannot write one either.
 */
class RecruitmentJobOfferPolicy
{
    public function __construct(private readonly RecruitmentScopeResolver $scopes) {}

    public function viewAny(User $user): bool
    {
        return $user->hasActivePermission('recruitment-applicants.view-offers')
            && $user->hasActivePermission('recruitment-applicants.view');
    }

    public function view(User $user, RecruitmentJobOffer $offer): bool
    {
        return $this->viewAny($user) && $this->inScope($user, $offer->application()->first());
    }

    public function viewSalary(User $user, RecruitmentJobOffer $offer): bool
    {
        return $user->canViewSalary() && $this->view($user, $offer);
    }

    /** Creating the offer for this application. */
    public function create(User $user, RecruitmentApplication $application): bool
    {
        return $this->canWrite($user) && $this->inScope($user, $application);
    }

    /** Edit a draft, send, revise, record the answer, cancel. */
    public function manage(User $user, RecruitmentJobOffer $offer): bool
    {
        return $this->canWrite($user) && $this->inScope($user, $offer->application()->first());
    }

    private function canWrite(User $user): bool
    {
        return $user->hasActivePermission('recruitment-applicants.manage-offers')
            && $this->viewAny($user)
            && $user->canViewSalary();
    }

    private function inScope(User $user, ?RecruitmentApplication $application): bool
    {
        return $application !== null && $this->scopes->canAccessApplication($user, $application);
    }
}
