<?php

namespace App\Policies;

use App\Enums\RecruitmentFileScanStatus;
use App\Models\RecruitmentApplicantFile;
use App\Models\User;
use App\Services\RecruitmentScopeResolver;
use Illuminate\Auth\Access\Response;

class RecruitmentApplicantFilePolicy
{
    public function __construct(private readonly RecruitmentScopeResolver $scopes) {}

    /**
     * Each denial carries a code so the download audit records why, not just that.
     * The permission is checked here rather than by route middleware so that a
     * refused attempt still reaches the controller and gets logged.
     */
    public function download(User $user, RecruitmentApplicantFile $file): Response
    {
        if (! $user->hasActivePermission('recruitment-campaigns.manage')) {
            return Response::deny(code: 'missing_permission');
        }

        if ($file->scan_status !== RecruitmentFileScanStatus::Clean) {
            return Response::deny(code: 'scan_'.$file->scan_status);
        }

        $request = $file->application()
            ->with('campaign.vacancy.createdFromRequest')
            ->first()?->campaign?->vacancy?->createdFromRequest;

        if ($request === null) {
            return Response::deny(code: 'no_scope');
        }

        return $this->scopes->canAccessRequest($user, $request)
            ? Response::allow()
            : Response::deny(code: 'out_of_scope');
    }
}
