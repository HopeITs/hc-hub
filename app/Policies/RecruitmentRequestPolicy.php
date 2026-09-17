<?php

namespace App\Policies;

use App\Models\RecruitmentRequest;
use App\Models\User;
use App\Services\RecruitmentScopeResolver;

class RecruitmentRequestPolicy
{
    public function __construct(private readonly RecruitmentScopeResolver $scopes) {}

    public function viewAny(User $user): bool
    {
        return $user->hasActivePermission('recruitment-requests.view');
    }

    public function view(User $user, RecruitmentRequest $request): bool
    {
        return $user->hasActivePermission('recruitment-requests.view')
            && $this->scopes->canAccessRequest($user, $request);
    }

    /**
     * Raising a request needs the permission *and* the job level.
     *
     * Eligibility comes from the org chart, not from a role an administrator
     * hands out (D26), which is also why super-admin cannot raise one: a
     * technical account holds no position, so it has no grade and no manager
     * to approve it (D41).
     */
    public function create(User $user, ?int $cityId = null): bool
    {
        return $user->hasActivePermission('recruitment-requests.manage')
            && $this->scopes->isEligibleRequester($user)
            && $this->canRaiseForCity($user, $cityId);
    }

    public function edit(User $user, RecruitmentRequest $request): bool
    {
        return $user->hasActivePermission('recruitment-requests.manage')
            && $this->scopes->canAccessRequest($user, $request);
    }

    public function update(User $user, RecruitmentRequest $request): bool
    {
        return $this->edit($user, $request);
    }

    public function submit(User $user, RecruitmentRequest $request): bool
    {
        return $user->hasActivePermission('recruitment-requests.submit')
            && $this->scopes->canAccessRequest($user, $request);
    }

    public function review(User $user, RecruitmentRequest $request): bool
    {
        return $user->hasActivePermission('recruitment-requests.approve')
            && $this->scopes->canAccessRequest($user, $request);
    }

    public function approve(User $user, RecruitmentRequest $request): bool
    {
        return $user->hasActivePermission('recruitment-requests.approve')
            && $this->scopes->canAccessRequest($user, $request);
    }

    public function reject(User $user, RecruitmentRequest $request): bool
    {
        return $user->hasActivePermission('recruitment-requests.reject')
            && $this->scopes->canAccessRequest($user, $request);
    }

    public function requestChanges(User $user, RecruitmentRequest $request): bool
    {
        return $user->hasActivePermission('recruitment-requests.request-changes')
            && $this->scopes->canAccessRequest($user, $request);
    }

    public function cancel(User $user, RecruitmentRequest $request): bool
    {
        return $user->hasActivePermission('recruitment-requests.cancel')
            && $this->scopes->canAccessRequest($user, $request);
    }

    /**
     * Opening a vacancy directly, for cases arranged outside the system (D15).
     */
    public function directOpen(User $user): bool
    {
        return $user->hasActivePermission('recruitment-requests.direct-open');
    }

    /**
     * Exporting respects the user's scope exactly and never widens it (D37):
     * each role exports what it is allowed to see, nothing more.
     */
    public function export(User $user, RecruitmentRequest $request): bool
    {
        return $user->hasActivePermission('recruitment-requests.export')
            && $this->scopes->canAccessRequest($user, $request);
    }

    /**
     * May this user raise a request scoped to this city?
     *
     * A null city is the empty create screen; every persistence path calls
     * this again with the submitted city. Organization-wide roles may raise
     * one anywhere, and a governorate HR inside the cities it reviews (D42).
     *
     * ASSUMPTION — not covered by a decision yet: a manager may raise a
     * request for the city of the position he himself holds. The decisions fix
     * what a manager may *see* (D30) but not which city he may raise a request
     * *for*. Confirm before relying on it.
     */
    private function canRaiseForCity(User $user, ?int $cityId): bool
    {
        if ($cityId === null || $this->scopes->isOrganizationWide($user)) {
            return true;
        }

        $governorates = $this->scopes->governorateCityIds($user);

        if ($governorates !== []) {
            return in_array($cityId, $governorates, true);
        }

        return (int) ($this->scopes->position($user)?->city_id ?? 0) === $cityId;
    }
}
