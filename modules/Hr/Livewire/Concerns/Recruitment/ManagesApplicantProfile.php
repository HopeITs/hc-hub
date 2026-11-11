<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Recruitment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Modules\Hr\Actions\Recruitment\RecruitmentApplicantNoteService;
use Modules\Hr\Actions\Recruitment\RecruitmentApplicationFlagService;
use Modules\Hr\Actions\Recruitment\RecruitmentApplicationParticipantService;
use Modules\Hr\Actions\Recruitment\RecruitmentApplicationReviewService;
use Modules\Hr\Actions\Recruitment\RecruitmentApplicationStatusService;
use Modules\Hr\Actions\Recruitment\RecruitmentCvArchiveService;
use Modules\Hr\Actions\Recruitment\RecruitmentEndorsementService;
use Modules\Hr\Actions\Recruitment\RecruitmentTagService;
use Modules\Hr\Models\RecruitmentApplicantNote;
use Modules\Hr\Models\RecruitmentApplication;
use Modules\Hr\Models\RecruitmentApplicationFlag;
use Modules\Hr\Models\RecruitmentApplicationParticipant;
use Modules\Hr\Models\RecruitmentApplicationReview;
use Modules\Hr\Models\RecruitmentTag;

/**
 * What a reviewer does on the profile: decide an application, archive a CV,
 * endorse, tag and flag, write and delete internal notes, grant profile
 * access and write their own review. A button only fixes the target; each
 * service validates the transition, the reason and the permission again.
 * Writing a note can never move an application: it has no status field.
 */
trait ManagesApplicantProfile
{
    #[Locked]
    public ?int $statusApplicationId = null;

    /** The status the reviewer was looking at when they opened the dialog. */
    #[Locked]
    public ?string $statusFrom = null;

    #[Locked]
    public string $statusTarget = '';

    public string $statusReasonCode = '';

    /** Internal: kept in the decision history, never sent to the candidate. */
    public string $statusReason = '';

    /** Rejections only: queue the apology email after the decision is saved. */
    public bool $statusNotify = true;

    public bool $showStatus = false;

    #[Locked]
    public ?int $archiveFileId = null;

    public string $archiveReason = '';

    public bool $showArchive = false;

    /** @var array<int, string> application id => the tag picked for it */
    public array $tagPick = [];

    #[Locked]
    public ?int $newTagApplicationId = null;

    public string $newTagName = '';

    public string $newTagColor = 'neutral';

    public string $newTagKind = RecruitmentTag::KIND_NEUTRAL;

    #[Locked]
    public ?int $flagApplicationId = null;

    public string $flagType = RecruitmentApplicationFlag::TYPE_RED;

    public string $flagCategory = 'technical';

    public string $flagReason = 'below_requirement';

    public string $flagNote = '';

    public bool $showFlag = false;

    /** Chosen in the browser, so re-resolved through the scope on save. */
    public ?int $noteApplicationId = null;

    public string $noteBody = '';

    public bool $noteSensitive = false;

    #[Locked]
    public ?int $deletingNoteId = null;

    public string $deletionReason = '';

    public bool $showNoteDelete = false;

    public string $newParticipantId = '';

    public string $newParticipantRole = RecruitmentApplicationParticipant::ROLE_REVIEWER;

    #[Locked]
    public ?int $reviewApplicationId = null;

    #[Locked]
    public ?int $expectedVersion = null;

    public string $recommendation = '';

    public string $pros = '';

    public string $cons = '';

    public string $reviewNotes = '';

    public bool $showReview = false;

    public bool $showErasure = false;

    // ---- decisions ----

    public function beginStatusChange(int $applicationId, string $target, RecruitmentApplicationStatusService $statuses): void
    {
        $application = $this->scopedApplication($applicationId);
        Gate::authorize('changeStatus', $application);
        abort_unless(in_array($target, $statuses->targetsFor($this->viewer(), $application), true), 403);

        $this->resetErrorBag();
        $this->statusApplicationId = $application->getKey();
        $this->statusFrom = (string) $application->status;
        $this->statusTarget = $target;
        $this->statusReasonCode = '';
        $this->statusReason = '';
        $this->statusNotify = true;
        $this->showStatus = true;
    }

    public function changeStatus(RecruitmentApplicationStatusService $statuses): void
    {
        abort_if($this->statusApplicationId === null || $this->statusFrom === null, 404);

        if (trim($this->statusReason) === '') {
            $this->addError('statusReason', __('hr_recruitment.applicant_ui.decisions.internal_note_required'));

            return;
        }

        $application = $this->scopedApplication($this->statusApplicationId);
        Gate::authorize('changeStatus', $application);

        if (! $this->attempt(fn () => $statuses->change($this->viewer(), $application, $this->statusFrom, $this->statusTarget, $this->statusReason, $this->statusReasonCode, $this->statusNotify),
            __('hr_recruitment.applicant_ui.status_change.changed'))) {
            return;
        }

        $this->reset('statusApplicationId', 'statusFrom', 'statusTarget', 'statusReasonCode', 'statusReason', 'statusNotify', 'showStatus');
        $this->forgetProfile();
    }

    // ---- CV archive ----

    public function beginArchive(int $fileId): void
    {
        $file = $this->resolveFile($fileId);
        Gate::authorize('archive', $file);

        $this->resetErrorBag();
        $this->archiveFileId = $file->getKey();
        $this->archiveReason = '';
        $this->showArchive = true;
    }

    public function archiveCv(RecruitmentCvArchiveService $archive): void
    {
        abort_if($this->archiveFileId === null, 404);
        $file = $this->resolveFile($this->archiveFileId);
        Gate::authorize('archive', $file);

        if ($this->attempt(fn () => $archive->archive($this->viewer(), $file, $this->archiveReason), __('hr_recruitment.applicant_ui.cv_archive.archived'))) {
            $this->reset('archiveFileId', 'archiveReason', 'showArchive');
            $this->forgetProfile();
        }
    }

    // ---- endorsement ----

    public function endorse(int $applicationId, RecruitmentEndorsementService $endorsements): void
    {
        $application = $this->scopedApplication($applicationId);

        if ($this->attempt(fn () => $endorsements->endorse($this->viewer(), $application), __('hr_recruitment.applicant_ui.endorsement.endorsed'))) {
            $this->forgetProfile();
        }
    }

    public function retractEndorsement(int $applicationId, RecruitmentEndorsementService $endorsements): void
    {
        $application = $this->scopedApplication($applicationId);

        if ($this->attempt(fn () => $endorsements->retract($this->viewer(), $application), __('hr_recruitment.applicant_ui.endorsement.retracted'))) {
            $this->forgetProfile();
        }
    }

    // ---- tags ----

    public function attachTag(int $applicationId, RecruitmentTagService $tags): void
    {
        $application = $this->scopedApplication($applicationId);
        Gate::authorize('tag', $application);

        if ($this->attempt(fn () => $tags->attach($this->viewer(), $application, $this->tagPick[$applicationId] ?? ''))) {
            unset($this->tagPick[$applicationId]);
            $this->forgetProfile();
        }
    }

    public function detachTag(int $applicationId, int $tagId, RecruitmentTagService $tags): void
    {
        $application = $this->scopedApplication($applicationId);
        Gate::authorize('tag', $application);

        if ($this->attempt(fn () => $tags->detach($this->viewer(), $application, $tagId))) {
            $this->forgetProfile();
        }
    }

    public function openNewTag(int $applicationId): void
    {
        $application = $this->scopedApplication($applicationId);
        Gate::authorize('tag', $application);
        Gate::authorize('create', RecruitmentTag::class);

        $this->resetErrorBag();
        $this->reset('newTagName', 'newTagColor', 'newTagKind');
        $this->newTagApplicationId = $application->getKey();
    }

    /** A new name goes on the shared tag list, so this also needs manage-tags. */
    public function createAndAttachTag(RecruitmentTagService $tags): void
    {
        abort_if($this->newTagApplicationId === null, 404);
        $application = $this->scopedApplication($this->newTagApplicationId);
        Gate::authorize('tag', $application);
        Gate::authorize('create', RecruitmentTag::class);

        // One transaction: a tag is never left on the list without the assignment it was made for.
        $done = $this->attempt(fn () => DB::transaction(function () use ($tags, $application): void {
            $tag = $tags->create($this->viewer(), $this->newTagName, $this->newTagColor, $this->newTagKind);
            $tags->attach($this->viewer(), $application, $tag->getKey());
        }));

        if ($done) {
            $this->reset('newTagApplicationId', 'newTagName', 'newTagColor', 'newTagKind');
            $this->forgetProfile();
        }
    }

    // ---- assessment flags ----

    public function openFlag(int $applicationId): void
    {
        $application = $this->scopedApplication($applicationId);
        Gate::authorize('tag', $application);

        $this->resetErrorBag();
        $this->reset('flagType', 'flagCategory', 'flagReason', 'flagNote');
        $this->flagApplicationId = $application->getKey();
        $this->showFlag = true;
    }

    public function updatedFlagCategory(): void
    {
        $this->flagReason = (string) (array_key_first(RecruitmentApplicationFlag::reasons()[$this->flagCategory] ?? []) ?? '');
    }

    public function createFlag(RecruitmentApplicationFlagService $flags): void
    {
        abort_if($this->flagApplicationId === null, 404);
        $application = $this->scopedApplication($this->flagApplicationId);
        Gate::authorize('tag', $application);

        if ($this->attempt(fn () => $flags->create($this->viewer(), $application, $this->flagType, $this->flagCategory, $this->flagReason, $this->flagNote))) {
            $this->reset('flagApplicationId', 'flagNote', 'showFlag');
            $this->forgetProfile();
        }
    }

    public function discardFlag(int $applicationId, int $flagId, RecruitmentApplicationFlagService $flags): void
    {
        $application = $this->scopedApplication($applicationId);
        Gate::authorize('tag', $application);
        $flag = $application->flags()->whereKey($flagId)->firstOrFail();

        if ($this->attempt(fn () => $flags->discard($this->viewer(), $flag))) {
            $this->forgetProfile();
        }
    }

    // ---- internal notes ----

    public function addNote(RecruitmentApplicantNoteService $notes): void
    {
        $applicant = $this->applicant;
        $application = null;

        if ($this->noteApplicationId !== null) {
            $application = RecruitmentApplication::query()->visibleTo($this->viewer())
                ->where('applicant_id', $applicant->getKey())
                ->find($this->noteApplicationId);

            if ($application === null) {
                $this->addError('noteApplicationId', __('hr_recruitment.applicant_ui.notes.application_mismatch'));

                return;
            }
        }

        Gate::authorize('create', [RecruitmentApplicantNote::class, $applicant, $application]);

        if ($this->attempt(fn () => $notes->add($this->viewer(), $applicant, $application, $this->noteBody, $this->noteSensitive))) {
            $this->reset('noteBody', 'noteSensitive');
            unset($this->notes);
        }
    }

    public function confirmNoteDelete(int $noteId): void
    {
        $note = $this->resolveNote($noteId);
        Gate::authorize('delete', $note);

        $this->resetErrorBag();
        $this->deletingNoteId = $note->getKey();
        $this->deletionReason = '';
        $this->showNoteDelete = true;
    }

    public function deleteNote(RecruitmentApplicantNoteService $notes): void
    {
        abort_if($this->deletingNoteId === null, 404);
        $note = $this->resolveNote($this->deletingNoteId);
        Gate::authorize('delete', $note);

        if ($this->attempt(fn () => $notes->delete($this->viewer(), $note, $this->deletionReason))) {
            $this->reset('deletingNoteId', 'deletionReason', 'showNoteDelete');
            unset($this->notes);
        }
    }

    // ---- profile access and reviews ----

    public function addParticipant(RecruitmentApplicationParticipantService $participants): void
    {
        $application = $this->accessApplication();
        $userId = filter_var($this->newParticipantId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($userId === false) {
            $this->addError('newParticipantId', __('hr_recruitment.applicant_ui.profile_reviews.errors.user_required'));

            return;
        }

        if ($this->attempt(fn () => $participants->assign($this->viewer(), $application, $userId, $this->newParticipantRole), __('hr_recruitment.applicant_ui.profile_reviews.assigned'))) {
            $this->reset('newParticipantId');
            $this->forgetProfile();
        }
    }

    public function removeParticipant(int $participantId, RecruitmentApplicationParticipantService $participants): void
    {
        $application = $this->accessApplication();

        if ($this->attempt(fn () => $participants->remove($this->viewer(), $application, $participantId), __('hr_recruitment.applicant_ui.profile_reviews.removed'))) {
            $this->forgetProfile();
        }
    }

    public function openReview(int $applicationId): void
    {
        $application = $this->resolveApplication($applicationId);
        Gate::authorize('submit', [RecruitmentApplicationReview::class, $application]);

        $review = RecruitmentApplicationReview::query()
            ->where('application_id', $application->getKey())
            ->where('reviewer_user_id', $this->viewer()->getKey())
            ->first();

        $this->resetErrorBag();
        $this->reviewApplicationId = $application->getKey();
        $this->expectedVersion = $review?->version;
        $this->recommendation = (string) $review?->recommendation;
        $this->pros = (string) $review?->pros;
        $this->cons = (string) $review?->cons;
        $this->reviewNotes = (string) $review?->notes;
        $this->showReview = true;
    }

    public function saveReview(RecruitmentApplicationReviewService $reviews): void
    {
        abort_if($this->reviewApplicationId === null, 404);
        $application = $this->resolveApplication($this->reviewApplicationId);
        Gate::authorize('submit', [RecruitmentApplicationReview::class, $application]);

        $saved = $this->attempt(fn () => $reviews->submit($this->viewer(), $application, [
            'recommendation' => $this->recommendation,
            'pros' => $this->pros,
            'cons' => $this->cons,
            'notes' => $this->reviewNotes,
        ], $this->expectedVersion), __('hr_recruitment.applicant_ui.profile_reviews.saved'));

        if ($saved) {
            $this->reset('reviewApplicationId', 'expectedVersion', 'recommendation', 'pros', 'cons', 'reviewNotes', 'showReview');
            $this->forgetProfile();
        }
    }

    /** Profile access is granted on the newest visible application, inside HR's normal scope. */
    private function accessApplication(): RecruitmentApplication
    {
        $application = $this->applications->first();
        abort_if($application === null, 404);
        $application = $this->scopedApplication($application->getKey());
        Gate::authorize('manageReviewParticipants', $application);

        return $application;
    }

    private function resolveNote(int $noteId): RecruitmentApplicantNote
    {
        return RecruitmentApplicantNote::query()
            ->visibleTo($this->viewer())
            ->where('applicant_id', $this->applicant->getKey())
            ->findOrFail($noteId);
    }
}
