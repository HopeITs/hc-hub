<?php

namespace Modules\Hr\Policies;

use Illuminate\Auth\Access\Response;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentScopeResolver;
use Modules\Hr\Enums\RecruitmentFileScanStatus;
use Modules\Hr\Models\RecruitmentApplicantFile;
use Modules\Hr\Support\Recruitment\Access;

class RecruitmentApplicantFilePolicy
{
    public function __construct(private readonly RecruitmentScopeResolver $scopes) {}

    /**
     * Each denial carries a code so the download audit records why, not just that.
     * The permission is checked here rather than by route middleware so that a
     * refused attempt still reaches the controller and gets logged.
     *
     * Downloading has its own permission. Seeing that a CV exists — on the
     * list or the profile — never implies being allowed to read it.
     */
    public function download(User $user, RecruitmentApplicantFile $file): Response
    {
        $isAssigned = $file->application?->applicant?->currentReviewParticipants()
            ->where('user_id', $user->getKey())->exists() ?? false;

        if (! Access::hasActivePermission($user, 'recruitment-applicants.download-cv') && ! $isAssigned) {
            return Response::deny(code: 'missing_permission');
        }

        if ($file->scan_status !== RecruitmentFileScanStatus::Clean) {
            return Response::deny(code: 'scan_'.$file->scan_status);
        }

        return ($this->inScope($user, $file) || $isAssigned) ? Response::allow() : Response::deny(code: 'out_of_scope');
    }

    public function archive(User $user, RecruitmentApplicantFile $file): bool
    {
        return Access::hasActivePermission($user, 'recruitment-applicants.archive-cv')
            && $file->isCv()
            && ! $file->isArchived()
            && $this->inScope($user, $file);
    }

    /**
     * A file with an application inherits that application's scope. One
     * without — its application gone — belongs to the applicant's own record.
     */
    private function inScope(User $user, RecruitmentApplicantFile $file): bool
    {
        if ($file->application_id !== null) {
            $application = $file->application;

            return $application !== null && $this->scopes->canAccessApplication($user, $application);
        }

        $applicant = $file->applicant;

        return $applicant !== null && $this->scopes->ownsApplicantRecord($user, $applicant);
    }
}
