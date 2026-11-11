<?php

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentScopeResolver;
use Modules\Hr\Models\RecruitmentWorkflowInstanceStep;
use Modules\Hr\Support\Recruitment\Access;

class RecruitmentWorkflowInstanceStepPolicy
{
    public function __construct(private readonly RecruitmentScopeResolver $scopes) {}

    public function view(User $user, RecruitmentWorkflowInstanceStep $step): bool
    {
        $request = $step->workflowInstance()->with('request')->first()?->request;

        return $request !== null
            && Access::hasActivePermission($user, 'recruitment-requests.view')
            && $this->scopes->canAccessRequest($user, $request);
    }

    public function approve(User $user, RecruitmentWorkflowInstanceStep $step): bool
    {
        return $this->canDecide($user, $step, 'recruitment-requests.approve');
    }

    public function reject(User $user, RecruitmentWorkflowInstanceStep $step): bool
    {
        return $this->canDecide($user, $step, 'recruitment-requests.reject');
    }

    public function requestChanges(User $user, RecruitmentWorkflowInstanceStep $step): bool
    {
        return $this->canDecide($user, $step, 'recruitment-requests.request-changes');
    }

    private function canDecide(User $user, RecruitmentWorkflowInstanceStep $step, string $permission): bool
    {
        return Access::hasActivePermission($user, $permission)
            && $this->scopes->canActOnStep($user, $step);
    }
}
