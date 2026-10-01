<?php

namespace App\Livewire\Recruitment\Applicants;

use App\Models\RecruitmentApplicant;
use App\Models\RecruitmentApplicantFile;
use App\Models\RecruitmentApplication;
use App\Services\RecruitmentApplicationStatusService;
use App\Services\RecruitmentCvArchiveService;
use App\Services\RecruitmentMatchingIndicators;
use App\Services\RecruitmentScopeResolver;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * An applicant's profile: who they are, what they applied for, their files.
 *
 * Every id that arrives from the browser — the applicant's, an
 * application's, a file's — is looked up again through the scope on every
 * request, and constrained to this applicant. A record outside that scope is
 * a 404, not a 403, so guessing ids does not reveal which ones exist.
 *
 * Decisions (shortlist, accept, reject, send back to review) and CV
 * archiving live here, each behind its own open / confirm pair. A decision
 * button only fixes the target; the service validates the transition, the
 * reason and the permission again. The notes panel is a separate component with no status
 * field at all, so writing a note can never move an application.
 */
class Show extends Component
{
    #[Locked]
    public int $applicantId;

    #[Locked]
    public ?int $statusApplicationId = null;

    /** The status the reviewer was looking at when they opened the panel. */
    #[Locked]
    public ?string $statusFrom = null;

    /** Fixed by the button that opened the panel. */
    #[Locked]
    public string $statusTarget = '';

    public string $statusReasonCode = '';

    /** Internal: kept in the decision history, never sent to the candidate. */
    public string $statusReason = '';

    /** Rejections only: queue the apology email after the decision is saved. */
    public bool $statusNotify = true;

    #[Locked]
    public ?int $archiveFileId = null;

    public string $archiveReason = '';

    public function mount(int $applicantId): void
    {
        $this->applicantId = $applicantId;
        $this->resolveApplicant();
    }

    public function beginStatusChange(int $applicationId, string $target, RecruitmentApplicationStatusService $statuses): void
    {
        $application = $this->resolveApplication($applicationId);
        Gate::authorize('changeStatus', $application);
        abort_unless(in_array($target, $statuses->targetsFor(auth()->user(), $application), true), 403);

        $this->closeArchivePanel();
        $this->resetErrorBag();
        $this->statusApplicationId = $application->getKey();
        $this->statusFrom = (string) $application->status;
        $this->statusTarget = $target;
        $this->statusReasonCode = '';
        $this->statusReason = '';
        $this->statusNotify = true;

        $this->dispatch('focus-element', id: 'decision-panel-'.$application->getKey());
    }

    public function changeStatus(RecruitmentApplicationStatusService $statuses): void
    {
        abort_if($this->statusApplicationId === null || $this->statusFrom === null, 404);

        if (! $this->hasInternalNote()) {
            return;
        }

        $application = $this->resolveApplication($this->statusApplicationId);
        Gate::authorize('changeStatus', $application);

        try {
            $statuses->change(
                auth()->user(),
                $application,
                $this->statusFrom,
                $this->statusTarget,
                $this->statusReason,
                $this->statusReasonCode,
                $this->statusNotify,
            );
        } catch (ValidationException $exception) {
            $this->addErrors($exception);

            return;
        }

        $this->closeStatusPanel();
        $this->toast(__('recruitment-applicant.status_change.changed'));

        // The button that confirmed is gone, and the one that opened the panel
        // may be too once the status has moved: the application itself is next.
        $this->dispatch('focus-element', id: 'application-'.$application->getKey().'-title');
    }

    /** Opens the shared recruitment confirmation popup after the note is validated. */
    public function openStatusConfirmation(): void
    {
        abort_if($this->statusApplicationId === null || $this->statusFrom === null || $this->statusTarget === '', 404);

        if (! $this->hasInternalNote()) {
            return;
        }

        $this->dispatch('open-recruitment-confirmation',
            scope: 'applicant-profile',
            action: 'changeStatus',
            title: __('recruitment-applicant.status_change.change_title'),
            message: __('recruitment-applicant.status_change.confirm'),
            confirmLabel: __('recruitment-applicant.decisions.confirm.'.$this->statusTarget),
            tone: $this->statusTarget === \App\Enums\RecruitmentApplicationStatus::Rejected ? 'danger' : 'primary',
        );
    }

    /** Back to the decision button that opened the panel. */
    public function cancelStatusChange(): void
    {
        $opener = 'decide-'.$this->statusApplicationId.'-'.$this->statusTarget;

        $this->closeStatusPanel();
        $this->dispatch('focus-element', id: $opener);
    }

    public function beginArchive(int $fileId): void
    {
        $file = $this->resolveFile($fileId);
        Gate::authorize('archive', $file);

        $this->closeStatusPanel();
        $this->resetErrorBag();
        $this->archiveFileId = $file->getKey();
        $this->archiveReason = '';

        $this->dispatch('focus-element', id: 'archive-panel-'.$file->getKey());
    }

    public function archiveCv(RecruitmentCvArchiveService $archive): void
    {
        abort_if($this->archiveFileId === null, 404);

        $file = $this->resolveFile($this->archiveFileId);
        Gate::authorize('archive', $file);

        try {
            $archive->archive(auth()->user(), $file, $this->archiveReason);
        } catch (ValidationException $exception) {
            $this->addErrors($exception);

            return;
        }

        $this->closeArchivePanel();
        $this->dispatch('recruitment-notes-changed');
        $this->toast(__('recruitment-applicant.cv_archive.archived'));

        // An archived CV loses its archive button; its row is what changed.
        $this->dispatch('focus-element', id: 'file-'.$file->getKey());
    }

    /** Back to the archive button that opened the panel. */
    public function cancelArchive(): void
    {
        $opener = 'archive-'.$this->archiveFileId;

        $this->closeArchivePanel();
        $this->dispatch('focus-element', id: $opener);
    }

    public function render(
        RecruitmentScopeResolver $scopes,
        RecruitmentApplicationStatusService $statuses,
        RecruitmentCvArchiveService $archive,
        RecruitmentMatchingIndicators $indicators,
    ) {
        $user = auth()->user();
        $applicant = $this->resolveApplicant();

        $applications = $applicant->applications()
            ->visibleTo($user)
            ->with([
                'campaign.vacancy.createdFromRequest.scopeCity',
                'campaign.vacancy.position',
                'files.archivedBy.profile',
                'files.archiveNote',
                'files.cvAnalysis',
                'decisions' => fn ($decisions) => $decisions->latest('decided_at')->latest('id'),
                'decisions.decidedBy.profile',
                'indicatorSnapshot',
                'rejectionNotice',
            ])
            ->latest('submitted_at')
            ->latest('id')
            ->get();

        $totalApplications = $applicant->applications()->count();

        // Files with no application left to inherit scope from belong to the
        // applicant's own record, and only its owner sees them.
        $orphanFiles = $scopes->ownsApplicantRecord($user, $applicant)
            ? $applicant->files()->whereNull('application_id')->with(['archivedBy.profile', 'archiveNote'])->latest('uploaded_at')->get()
            : collect();

        $archiveFile = $this->archiveFileId === null ? null : $this->resolveFile($this->archiveFileId);

        return view('livewire.recruitment.applicants.show', [
            'applicant' => $applicant,
            'applications' => $applications,
            'currentApplication' => $applications->first(),
            // A count only: what the other applications were stays out of scope.
            'hiddenApplications' => max(0, $totalApplications - $applications->count()),
            'isPreviousApplicant' => $totalApplications > 1,
            'orphanFiles' => $orphanFiles,
            'statusTargets' => $this->statusTargets($applications, $statuses),
            'statusNeedsReason' => $this->statusFrom !== null && $this->statusTarget !== ''
                && $statuses->requiresReason($this->statusFrom, $this->statusTarget, $this->statusReasonCode ?: null),
            'rejectionReasons' => $statuses->rejectionReasons(),
            'archiveFile' => $archiveFile,
            'archiveNeedsReason' => $archiveFile !== null && $archive->requiresReason($archiveFile),
            // Read-only: evaluating indicators never touches the application.
            'indicatorSets' => $indicators->evaluateMany($applications),
        ]);
    }

    private function closeStatusPanel(): void
    {
        $this->reset('statusApplicationId', 'statusFrom', 'statusTarget', 'statusReasonCode', 'statusReason', 'statusNotify');
        $this->resetErrorBag(['statusTarget', 'statusReasonCode', 'statusReason']);
    }

    private function closeArchivePanel(): void
    {
        $this->reset('archiveFileId', 'archiveReason');
        $this->resetErrorBag('archiveReason');
    }

    /** @return array<int, array<int, string>> keyed by application id */
    private function statusTargets(Collection $applications, RecruitmentApplicationStatusService $statuses): array
    {
        return $applications
            ->mapWithKeys(fn (RecruitmentApplication $application): array => [
                $application->getKey() => $statuses->targetsFor(auth()->user(), $application),
            ])
            ->all();
    }

    private function resolveApplicant(): RecruitmentApplicant
    {
        $applicant = RecruitmentApplicant::query()
            ->visibleTo(auth()->user())
            ->with(['city', 'employee.media'])
            ->findOrFail($this->applicantId);

        Gate::authorize('view', $applicant);

        return $applicant;
    }

    private function resolveApplication(int $applicationId): RecruitmentApplication
    {
        $this->resolveApplicant();

        $application = RecruitmentApplication::query()
            ->visibleTo(auth()->user())
            ->where('applicant_id', $this->applicantId)
            ->findOrFail($applicationId);

        Gate::authorize('view', $application);

        return $application;
    }

    private function resolveFile(int $fileId): RecruitmentApplicantFile
    {
        $applicant = $this->resolveApplicant();

        $file = RecruitmentApplicantFile::query()
            ->where('applicant_id', $this->applicantId)
            ->with('application')
            ->findOrFail($fileId);

        $visible = $file->application_id === null
            ? app(RecruitmentScopeResolver::class)->ownsApplicantRecord(auth()->user(), $applicant)
            : RecruitmentApplication::query()->visibleTo(auth()->user())->whereKey($file->application_id)->exists();

        if (! $visible) {
            throw (new ModelNotFoundException)->setModel(RecruitmentApplicantFile::class, [$fileId]);
        }

        return $file;
    }

    private function addErrors(ValidationException $exception): void
    {
        foreach ($exception->errors() as $field => $messages) {
            $this->addError($field, $messages[0]);
        }
    }

    private function hasInternalNote(): bool
    {
        if (trim($this->statusReason) !== '') {
            return true;
        }

        $this->addError('statusReason', __('recruitment-applicant.decisions.internal_note_required'));

        return false;
    }

    /** Uses the application-wide SweetAlert toast instead of moving page content. */
    private function toast(string $message): void
    {
        LivewireAlert::title($message)
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }
}
