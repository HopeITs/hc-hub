<?php

namespace App\Policies;

use App\Enums\RecruitmentFileScanStatus;
use App\Models\RecruitmentApplicantFile;
use App\Models\User;
use App\Services\RecruitmentScopeResolver;

class RecruitmentApplicantFilePolicy
{
    public function __construct(private readonly RecruitmentScopeResolver $scopes) {}

    public function download(User $user, RecruitmentApplicantFile $file): bool
    {
        if ($file->scan_status !== RecruitmentFileScanStatus::Clean
            || ! $user->hasActivePermission('recruitment-campaigns.manage')) {
            return false;
        }

        $request = $file->application()
            ->with('campaign.vacancy.createdFromRequest')
            ->first()?->campaign?->vacancy?->createdFromRequest;

        return $request !== null && $this->scopes->canAccessRequest($user, $request);
    }
}
