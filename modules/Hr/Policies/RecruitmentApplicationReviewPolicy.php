<?php

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Models\RecruitmentApplication;
use Modules\Hr\Models\RecruitmentApplicationParticipant;
use Modules\Hr\Models\RecruitmentApplicationReview;

class RecruitmentApplicationReviewPolicy
{
    public function submit(User $user, RecruitmentApplication $application): bool
    {
        return $application->applicant?->currentReviewParticipants()
            ->where('user_id', $user->getKey())
            ->where('role', RecruitmentApplicationParticipant::ROLE_REVIEWER)
            ->exists() ?? false;
    }

    public function view(User $user, RecruitmentApplicationReview $review): bool
    {
        return (int) $review->reviewer_user_id === (int) $user->getKey()
            || $user->can('manageReviewParticipants', $review->application);
    }
}
