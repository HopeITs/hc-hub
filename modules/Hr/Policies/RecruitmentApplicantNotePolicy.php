<?php

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentScopeResolver;
use Modules\Hr\Models\RecruitmentApplicant;
use Modules\Hr\Models\RecruitmentApplicantNote;
use Modules\Hr\Models\RecruitmentApplication;
use Modules\Hr\Support\Recruitment\Access;

/**
 * Notes are HR-internal and scoped by what they are attached to.
 *
 * Both checks read the same boundary as the list that displays the notes
 * (RecruitmentScopeResolver::scopeApplicantNotes), so a note id guessed from
 * another applicant or governorate is refused here even though the page
 * never rendered it.
 */
class RecruitmentApplicantNotePolicy
{
    public function __construct(private readonly RecruitmentScopeResolver $scopes) {}

    public function create(User $user, RecruitmentApplicant $applicant, ?RecruitmentApplication $application = null): bool
    {
        if (! Access::hasActivePermission($user, 'recruitment-applicants.add-notes')
            || ! $this->scopes->canAccessApplicant($user, $applicant)) {
            return false;
        }

        if ($application === null) {
            return $this->scopes->ownsApplicantRecord($user, $applicant);
        }

        return (int) $application->applicant_id === (int) $applicant->getKey()
            && $this->scopes->canAccessApplication($user, $application);
    }

    public function delete(User $user, RecruitmentApplicantNote $note): bool
    {
        return Access::hasActivePermission($user, 'recruitment-applicants.delete-notes')
            && $note->isDeletable()
            && ! $note->trashed()
            && RecruitmentApplicantNote::query()->visibleTo($user)->whereKey($note->getKey())->exists();
    }
}
