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
        return $user->hasActivePermission('recruitment.requests.view');
    }

    public function view(User $user, RecruitmentRequest $request): bool
    {
        return $user->hasActivePermission('recruitment.requests.view')
            && $this->scopes->canAccessRequest($user, $request);
    }

    public function create(User $user, ?int $cityId = null): bool
    {
        return $user->hasActivePermission('recruitment.requests.create')
            && $this->scopes->canCreateRequests($user)
            && $this->scopes->canCreateInCity($user, $cityId);
    }

    public function edit(User $user, RecruitmentRequest $request): bool
    {
        return $user->hasActivePermission('recruitment.requests.edit')
            && $this->scopes->canAccessRequest($user, $request);
    }

    public function update(User $user, RecruitmentRequest $request): bool
    {
        return $this->edit($user, $request);
    }

    public function submit(User $user, RecruitmentRequest $request): bool
    {
        return $user->hasActivePermission('recruitment.requests.submit')
            && $this->scopes->canAccessRequest($user, $request);
    }

    public function review(User $user, RecruitmentRequest $request): bool
    {
        return $user->hasActivePermission('recruitment.requests.review')
            && $this->scopes->canAccessRequest($user, $request);
    }

    public function export(User $user, RecruitmentRequest $request): bool
    {
        return $user->hasActivePermission('recruitment.requests.export')
            && $this->scopes->canAccessRequest($user, $request);
    }
}
