<?php

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentScopeResolver;
use Modules\Hr\Models\HiringCampaign;
use Modules\Hr\Models\PublicRecruitmentLink;
use Modules\Hr\Support\Recruitment\Access;

class PublicRecruitmentLinkPolicy
{
    public function __construct(private readonly RecruitmentScopeResolver $scopes) {}

    public function viewAny(User $user): bool
    {
        return Access::hasActivePermission($user, 'recruitment-links.view')
            || Access::hasActivePermission($user, 'recruitment-links.manage');
    }

    public function manage(User $user, PublicRecruitmentLink $link): bool
    {
        if (! Access::hasActivePermission($user, 'recruitment-links.manage')) {
            return false;
        }

        $request = $link->campaign()->with('vacancy.createdFromRequest')->first()?->vacancy?->createdFromRequest;

        return $request !== null && $this->scopes->canAccessRequest($user, $request);
    }

    public function create(User $user, HiringCampaign $campaign): bool
    {
        $request = $campaign->vacancy()->with('createdFromRequest')->first()?->createdFromRequest;

        return $request !== null
            && Access::hasActivePermission($user, 'recruitment-links.manage')
            && $this->scopes->canAccessRequest($user, $request);
    }

    public function update(User $user, PublicRecruitmentLink $link): bool
    {
        return $this->manage($user, $link);
    }

    public function delete(User $user, PublicRecruitmentLink $link): bool
    {
        return $this->manage($user, $link);
    }
}
