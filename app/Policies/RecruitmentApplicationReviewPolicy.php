<?php

namespace App\Policies;

use App\Models\RecruitmentApplication;
use App\Models\RecruitmentApplicationParticipant;
use App\Models\RecruitmentApplicationReview;
use App\Models\User;

class RecruitmentApplicationReviewPolicy
{
    public function submit(User $user, RecruitmentApplication $application): bool
    {
        return $application->currentReviewParticipants()
            ->where('user_id', $user->getKey())
            ->where('role', RecruitmentApplicationParticipant::ROLE_REVIEWER)
            ->exists();
    }

    public function view(User $user, RecruitmentApplicationReview $review): bool
    {
        return (int) $review->reviewer_user_id === (int) $user->getKey()
            || $user->can('manageReviewParticipants', $review->application);
    }
}
