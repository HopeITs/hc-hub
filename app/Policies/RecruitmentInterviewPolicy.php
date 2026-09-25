<?php

namespace App\Policies;

use App\Models\RecruitmentApplication;
use App\Models\RecruitmentInterview;
use App\Models\User;
use App\Services\RecruitmentScopeResolver;

/**
 * Phase 2 Task 7.
 *
 * - Scheduling, editing, rescheduling, cancelling and recording an outcome:
 *   HR holding recruitment-applicants.schedule-interviews, on an application
 *   inside their recruitment scope. Nobody else, participants included.
 * - Seeing an interview: whoever is currently on it, or anyone who can read
 *   applicants and has the application in scope.
 * - The meeting link: whoever may manage the interview, or who is currently
 *   on it. An HR reader who is neither sees that the interview is online,
 *   not how to join it.
 */
class RecruitmentInterviewPolicy
{
    public function __construct(private readonly RecruitmentScopeResolver $scopes) {}

    /** The interviews page itself; its query narrows to what each user may see. */
    public function viewAny(User $user): bool
    {
        return $user->hasActivePermission('recruitment-applicants.view')
            || RecruitmentInterview::hasAnyFor($user);
    }

    public function view(User $user, RecruitmentInterview $interview): bool
    {
        if ($interview->isParticipant($user)) {
            return true;
        }

        return $user->hasActivePermission('recruitment-applicants.view')
            && $this->inScope($user, $interview);
    }

    /** Scheduling a new interview for this application. */
    public function schedule(User $user, RecruitmentApplication $application): bool
    {
        return $user->hasActivePermission('recruitment-applicants.schedule-interviews')
            && $user->hasActivePermission('recruitment-applicants.view')
            && $this->scopes->canAccessApplication($user, $application);
    }

    /** Edit, reschedule, cancel, record the outcome. */
    public function manage(User $user, RecruitmentInterview $interview): bool
    {
        return $user->hasActivePermission('recruitment-applicants.schedule-interviews')
            && $user->hasActivePermission('recruitment-applicants.view')
            && $this->inScope($user, $interview);
    }

    public function viewMeetingLink(User $user, RecruitmentInterview $interview): bool
    {
        return $this->manage($user, $interview) || $interview->isParticipant($user);
    }

    private function inScope(User $user, RecruitmentInterview $interview): bool
    {
        $application = $interview->relationLoaded('application')
            ? $interview->application
            : $interview->application()->first();

        return $application !== null && $this->scopes->canAccessApplication($user, $application);
    }
}
