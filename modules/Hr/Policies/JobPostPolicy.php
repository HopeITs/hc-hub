<?php

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentScopeResolver;
use Modules\Hr\Models\HiringCampaign;
use Modules\Hr\Models\JobPost;
use Modules\Hr\Support\Recruitment\Access;

class JobPostPolicy
{
    public function __construct(private readonly RecruitmentScopeResolver $scopes) {}

    public function viewAny(User $user): bool
    {
        return Access::hasActivePermission($user, 'recruitment-job-posts.view');
    }

    public function view(User $user, JobPost $post): bool
    {
        return Access::hasActivePermission($user, 'recruitment-job-posts.view') && $this->inScope($user, $post);
    }

    public function create(User $user, HiringCampaign $campaign): bool
    {
        $request = $campaign->vacancy()->with('createdFromRequest')->first()?->createdFromRequest;

        return $request !== null
            && Access::hasActivePermission($user, 'recruitment-job-posts.manage')
            && $this->scopes->canAccessRequest($user, $request);
    }

    public function manage(User $user, JobPost $post): bool
    {
        return Access::hasActivePermission($user, 'recruitment-job-posts.manage') && $this->inScope($user, $post);
    }

    public function update(User $user, JobPost $post): bool
    {
        return $this->manage($user, $post);
    }

    public function delete(User $user, JobPost $post): bool
    {
        return $this->manage($user, $post);
    }

    public function publish(User $user, JobPost $post): bool
    {
        return Access::hasActivePermission($user, 'recruitment-job-posts.manage') && $this->inScope($user, $post);
    }

    private function inScope(User $user, JobPost $post): bool
    {
        $request = $post->campaign()->with('vacancy.createdFromRequest')->first()?->vacancy?->createdFromRequest;

        return $request !== null && $this->scopes->canAccessRequest($user, $request);
    }
}
