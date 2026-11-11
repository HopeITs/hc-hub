<?php

namespace Modules\Hr\Policies;

use Illuminate\Auth\Access\Response;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentScopeResolver;
use Modules\Hr\Models\RecruitmentApplicant;
use Modules\Hr\Models\RecruitmentApplicationParticipant;
use Modules\Hr\Support\Recruitment\Access;

class RecruitmentApplicantPolicy
{
    public function __construct(private readonly RecruitmentScopeResolver $scopes) {}

    public function viewAny(User $user): bool
    {
        return Access::hasActivePermission($user, 'recruitment-applicants.view')
            || RecruitmentApplicationParticipant::query()
                ->current()
                ->where('user_id', $user->getKey())
                ->exists();
    }

    public function view(User $user, RecruitmentApplicant $applicant): bool
    {
        if ($this->viewAny($user) && $this->scopes->canAccessApplicant($user, $applicant)) {
            return true;
        }

        return $applicant->currentReviewParticipants()->where('user_id', $user->getKey())->exists();
    }

    /**
     * Anonymizing an applicant on their request.
     *
     * Asked without an applicant it answers only "may they do this at all", so
     * a caller without the permission is refused before the existence of an id
     * is confirmed or denied. With one it also needs the whole of the
     * applicant in scope: erasing wipes every application, and a governorate
     * HR must not erase what another governorate's campaign still holds.
     *
     * Like export, the permission is read with hasExplicitPermission().
     */
    public function requestErasure(User $user, ?RecruitmentApplicant $applicant = null): Response
    {
        if (! Access::hasExplicitPermission($user, 'recruitment-applicants.request-erasure')
            || ! $this->viewAny($user)) {
            return Response::deny(code: 'missing_permission');
        }

        if ($applicant === null) {
            return Response::allow();
        }

        return $this->scopes->coversWholeApplicant($user, $applicant)
            ? Response::allow()
            : Response::deny(code: 'out_of_scope');
    }
}
