<?php

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentScopeResolver;
use Modules\Hr\Models\RecruitmentRequestEvent;
use Modules\Hr\Support\Recruitment\Access;

class RecruitmentRequestEventPolicy
{
    public function __construct(private readonly RecruitmentScopeResolver $scopes) {}

    public function viewAny(User $user): bool
    {
        return Access::hasActivePermission($user, 'recruitment-requests.view');
    }

    public function view(User $user, RecruitmentRequestEvent $event): bool
    {
        $request = $event->request()->first();

        return $request !== null
            && Access::hasActivePermission($user, 'recruitment-requests.view')
            && $this->scopes->canAccessRequest($user, $request);
    }

    public function export(User $user, RecruitmentRequestEvent $event): bool
    {
        $request = $event->request()->first();

        return $request !== null
            && Access::hasActivePermission($user, 'recruitment-requests.export')
            && $this->scopes->canAccessRequest($user, $request);
    }
}
