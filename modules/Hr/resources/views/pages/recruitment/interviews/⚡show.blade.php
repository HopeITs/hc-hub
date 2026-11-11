<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Hr\Actions\Recruitment\RecruitmentInterviewEvaluationService;
use Modules\Hr\Actions\Recruitment\RecruitmentInterviewSchedulingService;
use Modules\Hr\Enums\RecruitmentInterviewStatus;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Livewire\Concerns\Recruitment\ReadsInterviews;
use Modules\Hr\Models\RecruitmentInterview;
use Modules\Hr\Models\RecruitmentInterviewEvaluation;
use Modules\Hr\Support\Recruitment\Media\HrMedia;
use Modules\Hr\Support\Recruitment\RecruitmentConfig;

/*
 * One interview, seen by its participants, the requester of the position
 * and HR in scope. The meeting link is rendered only through the
 * viewMeetingLink ability and is never a public property.
 *
 * HR who manage it can edit or reschedule it, cancel it, or record how it
 * went (held / no-show) once it has started; each action opens a dialog,
 * and the status and start time seen when it opened are locked and checked
 * by the service, so a change made by someone else in between is refused.
 *
 * A reviewer writes their own evaluation here (only ever their own, with
 * the version it was opened on locked); HR with view-evaluations read every
 * evaluation, add a separate opinion note, and close evaluation once held.
 */
new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.interview')] class extends Component
{
    use AuthorizesRecruitment;
    use ReadsInterviews;
    use WithFileUploads;

    #[Locked]
    public int $interviewId;

    /** 'cancel' | 'completed' | 'no_show' | null */
    #[Locked]
    public ?string $panel = null;

    public bool $panelOpen = false;

    #[Locked]
    public ?string $expectedStatus = null;

    #[Locked]
    public ?string $expectedStartsAt = null;

    public string $cancelReason = '';

    /** Internal: kept on the interview, never sent to the candidate. */
    public string $cancelNote = '';

    public bool $notifyCandidate = true;

    public string $outcomeNote = '';

    public mixed $attachment = null;

    /** The viewer's own evaluation: the version it was loaded from, null until the first save. */
    #[Locked]
    public ?int $expectedVersion = null;

    public string $recommendation = '';

    public string $pros = '';

    public string $cons = '';

    public string $notes = '';

    /** HR's opinion note on one evaluation. */
    #[Locked]
    public ?int $noteEvaluationId = null;

    public bool $showNote = false;

    public string $opinionNote = '';

    public bool $showCloseEvaluations = false;

    public function mount(int $interviewId): void
    {
        $this->interviewId = $interviewId;
        $this->findInterview($interviewId);
        $this->loadOwn();

        if ($message = session()->pull('recruitment_toast')) {
            $this->toast($message);
        }
    }

    #[Computed]
    public function interview(): RecruitmentInterview
    {
        return $this->findInterview($this->interviewId)->load([
            'application:id,applicant_id,campaign_id,vacancy_id,status',
            'application.applicant:id,full_name',
            'application.vacancy:id,position_id',
            'application.vacancy.position:id,title_en,title_ar',
            'participants' => fn ($participants) => $participants->orderByRaw('removed_at is not null')->orderBy('id'),
            'requiredParticipants',
            'reschedules' => fn ($history) => $history->latest('rescheduled_at')->latest('id'),
        ]);
    }

    /** @return array<string, mixed> abilities and reads for one render */
    #[Computed]
    public function state(): array
    {
        $interview = $this->interview;
        $canManage = Gate::allows('manage', $interview);
        $evaluations = app(RecruitmentInterviewEvaluationService::class);
        $canEvaluate = Gate::allows('evaluate', $interview);
        $own = $canEvaluate ? $this->own()?->load(['revisions' => fn ($r) => $r->latest('version')]) : null;
        $canViewEvaluations = Gate::allows('viewEvaluations', $interview);

        return [
            'canManage' => $canManage,
            'canSeeApplicant' => Gate::allows('viewApplicant', $interview),
            // Rendered only when allowed; never stored on the component.
            'meetingUrl' => Gate::allows('viewMeetingLink', $interview) ? $interview->meeting_url : null,
            'hasStarted' => ! $interview->scheduled_at->isFuture(),
            'notices' => $canManage ? $interview->notices()->latest('id')->get() : collect(),
            'attachments' => $interview->getMedia(RecruitmentInterview::ATTACHMENTS),
            'active' => $this->activeUserIds($interview->participants->pluck('user_id')->all()),
            'canEvaluate' => $canEvaluate,
            'own' => $own,
            'closedReason' => $evaluations->closedReason($interview),
            'evaluationDue' => $canEvaluate && $evaluations->closedReason($interview) === null && $own === null,
            'canViewEvaluations' => $canViewEvaluations,
            'summary' => $canViewEvaluations ? $evaluations->summary($interview) : null,
            'evaluations' => $canViewEvaluations ? $interview->evaluations()
                ->with([
                    'revisions' => fn ($revisions) => $revisions->latest('version'),
                    'opinionNotes' => fn ($notes) => $notes->oldest(),
                ])
                ->orderByRaw('withdrawn_at is not null')
                ->orderBy('submitted_at')
                ->get() : collect(),
            'canClose' => Gate::allows('closeEvaluations', $interview)
                && $interview->status === RecruitmentInterviewStatus::Completed
                && $interview->evaluations_closed_at === null,
            'maxLength' => (int) RecruitmentConfig::get('recruitment.evaluations.max_text_length', 5000),
        ];
    }

    public function beginCancel(): void
    {
        $this->openPanel('cancel');
    }

    public function beginOutcome(string $outcome): void
    {
        abort_unless(in_array($outcome, [RecruitmentInterviewStatus::Completed, RecruitmentInterviewStatus::NoShow], true), 403);
        $this->openPanel($outcome);
    }

    public function dismissPanel(): void
    {
        $this->reset('panel', 'panelOpen', 'expectedStatus', 'expectedStartsAt', 'cancelReason', 'cancelNote', 'notifyCandidate', 'outcomeNote');
        $this->resetErrorBag();
    }

    public function cancelInterview(RecruitmentInterviewSchedulingService $scheduling): void
    {
        abort_unless($this->panel === 'cancel', 404);
        $interview = $this->findInterview($this->interviewId);
        Gate::authorize('manage', $interview);

        $this->runPanel(fn () => $scheduling->cancel(
            $this->viewer(),
            $interview,
            (string) $this->expectedStatus,
            (string) $this->expectedStartsAt,
            $this->cancelReason,
            $this->cancelNote,
            $this->notifyCandidate,
        ), __('hr_recruitment.applicant_ui.interviews.cancelled'));
    }

    public function recordOutcome(RecruitmentInterviewSchedulingService $scheduling): void
    {
        abort_unless(in_array($this->panel, [RecruitmentInterviewStatus::Completed, RecruitmentInterviewStatus::NoShow], true), 404);
        $interview = $this->findInterview($this->interviewId);
        Gate::authorize('manage', $interview);

        $this->runPanel(fn () => $scheduling->recordOutcome(
            $this->viewer(),
            $interview,
            (string) $this->expectedStatus,
            (string) $this->expectedStartsAt,
            (string) $this->panel,
            $this->outcomeNote,
        ), __('hr_recruitment.applicant_ui.interviews.outcome_recorded'));
    }

    public function uploadAttachment(): void
    {
        $interview = $this->findInterview($this->interviewId);
        Gate::authorize('manage', $interview);

        $this->validate([
            'attachment' => [
                'required', 'file', 'max:12288',
                'mimetypes:audio/mpeg,audio/mp4,audio/x-m4a,audio/wav,audio/x-wav,video/mp4,video/webm,application/pdf,image/jpeg,image/png',
            ],
        ], [
            'attachment.required' => __('hr_recruitment.applicant_ui.interviews.attachments.required'),
            'attachment.file' => __('hr_recruitment.applicant_ui.interviews.attachments.invalid'),
            'attachment.max' => __('hr_recruitment.applicant_ui.interviews.attachments.too_large'),
            'attachment.mimetypes' => __('hr_recruitment.applicant_ui.interviews.attachments.invalid'),
        ]);

        $originalName = basename($this->attachment->getClientOriginalName());
        $extension = strtolower((string) $this->attachment->getClientOriginalExtension());

        $interview
            ->addMedia($this->attachment->getRealPath())
            ->usingName(Str::limit(pathinfo($originalName, PATHINFO_FILENAME), 120, ''))
            ->usingFileName(Str::uuid().($extension !== '' ? '.'.$extension : ''))
            ->withCustomProperties([
                'original_name' => $originalName,
                'uploaded_by_user_id' => $this->viewer()->getKey(),
            ])
            ->toMediaCollection(RecruitmentInterview::ATTACHMENTS);

        $this->reset('attachment');
        unset($this->state);
        $this->toast(__('hr_recruitment.applicant_ui.interviews.attachments.uploaded'));
    }

    public function discardAttachment(int $mediaId): void
    {
        $interview = $this->findInterview($this->interviewId);
        Gate::authorize('manage', $interview);

        HrMedia::query()
            ->where('model_type', RecruitmentInterview::class)
            ->where('model_id', $interview->getKey())
            ->where('collection_name', RecruitmentInterview::ATTACHMENTS)
            ->findOrFail($mediaId)
            ->deleteWithFile();

        unset($this->state);
        $this->toast(__('hr_recruitment.applicant_ui.interviews.attachments.removed'));
    }

    /** The viewer's own evaluation; the service checks the locked version, so a stale tab is refused. */
    public function saveEvaluation(RecruitmentInterviewEvaluationService $evaluations): void
    {
        $this->resetErrorBag();
        $interview = $this->findInterview($this->interviewId);
        Gate::authorize('evaluate', $interview);

        try {
            $evaluation = $evaluations->submit($this->viewer(), $interview, [
                'recommendation' => $this->recommendation,
                'pros' => $this->pros,
                'cons' => $this->cons,
                'notes' => $this->notes,
            ], $this->expectedVersion);
        } catch (ValidationException $exception) {
            $this->fail($exception);

            return;
        }

        $this->expectedVersion = $evaluation->version;
        unset($this->state);
        $this->toast(__('hr_recruitment.applicant_ui.evaluations.saved'));
    }

    /** Discards unsaved edits and reloads what is stored: the way out of a stale save. */
    public function reloadEvaluation(): void
    {
        $this->resetErrorBag();
        $this->loadOwn();
        unset($this->state);
    }

    public function beginNote(int $evaluationId): void
    {
        $evaluation = $this->findEvaluation($evaluationId);
        Gate::authorize('addNote', $evaluation);

        $this->resetErrorBag();
        $this->noteEvaluationId = $evaluation->getKey();
        $this->opinionNote = '';
        $this->showNote = true;
    }

    public function closeNote(): void
    {
        $this->reset('noteEvaluationId', 'showNote', 'opinionNote');
        $this->resetErrorBag();
    }

    public function addNote(RecruitmentInterviewEvaluationService $evaluations): void
    {
        abort_if($this->noteEvaluationId === null, 404);
        $evaluation = $this->findEvaluation($this->noteEvaluationId);
        Gate::authorize('addNote', $evaluation);

        try {
            $evaluations->addNote($this->viewer(), $evaluation, $this->opinionNote);
        } catch (ValidationException $exception) {
            $this->fail($exception);

            return;
        }

        $this->closeNote();
        unset($this->state);
        $this->toast(__('hr_recruitment.applicant_ui.evaluations.note_added'));
    }

    public function closeEvaluations(RecruitmentInterviewEvaluationService $evaluations): void
    {
        $interview = $this->findInterview($this->interviewId);
        Gate::authorize('closeEvaluations', $interview);

        try {
            $evaluations->close($this->viewer(), $interview, (string) $interview->status);
        } catch (ValidationException $exception) {
            $this->showCloseEvaluations = false;
            $this->fail($exception);

            return;
        }

        $this->showCloseEvaluations = false;
        unset($this->interview, $this->state);
        $this->toast(__('hr_recruitment.applicant_ui.evaluations.closed_done'));
    }

    private function openPanel(string $panel): void
    {
        $interview = $this->findInterview($this->interviewId);
        Gate::authorize('manage', $interview);
        abort_unless($interview->isOpen(), 403);

        $this->dismissPanel();
        $this->panel = $panel;
        $this->panelOpen = true;
        $this->expectedStatus = $interview->status;
        $this->expectedStartsAt = $interview->scheduled_at->toIso8601String();
    }

    private function runPanel(callable $action, string $message): void
    {
        try {
            $action();
        } catch (ValidationException $exception) {
            $this->fail($exception);

            return;
        }

        $this->dismissPanel();
        unset($this->interview, $this->state);
        $this->loadOwn();
        $this->toast($message);
    }

    /** Field errors go to the bag; a whole-record refusal (`interview`, `evaluation`) also shows as a toast. */
    private function fail(ValidationException $exception): void
    {
        foreach ($exception->errors() as $field => $messages) {
            $this->addError($field, $messages[0]);
        }

        $this->toast(collect($exception->errors())->flatten()->first() ?? __('hr_recruitment.applicant_ui.interviews.errors.not_allowed'), false);
    }

    private function loadOwn(): void
    {
        $interview = $this->findInterview($this->interviewId);
        $own = Gate::allows('evaluate', $interview) ? $this->own() : null;

        $this->expectedVersion = $own?->version;
        $this->recommendation = (string) $own?->recommendation;
        $this->pros = (string) $own?->pros;
        $this->cons = (string) $own?->cons;
        $this->notes = (string) $own?->notes;
    }

    private function own(): ?RecruitmentInterviewEvaluation
    {
        return RecruitmentInterviewEvaluation::query()
            ->where('interview_id', $this->interviewId)
            ->where('reviewer_user_id', $this->viewer()->getKey())
            ->first();
    }

    private function findEvaluation(int $evaluationId): RecruitmentInterviewEvaluation
    {
        $interview = $this->findInterview($this->interviewId);
        Gate::authorize('viewEvaluations', $interview);

        return RecruitmentInterviewEvaluation::query()->where('interview_id', $interview->getKey())->findOrFail($evaluationId);
    }
};
?>

@php
    $interview = $this->interview;
    $state = $this->state;
    $t = 'hr_recruitment.applicant_ui.interviews.';
    $e = 'hr_recruitment.applicant_ui.evaluations.';
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $timezone = $interview->timezone ?: config('app.timezone');
    $starts = $interview->scheduled_at->copy()->setTimezone($timezone);
    $ends = $interview->ends_at?->copy()->setTimezone($timezone);
    $application = $interview->application;
    $directory = \Modules\Core\Models\User::directory();
    $who = fn ($userId) => $userId === null ? '—' : ($directory[(int) $userId]['name'] ?? '#'.$userId);
    $at = fn ($date) => $date?->copy()->setTimezone($timezone)->format('Y-m-d H:i');
    $canManage = $state['canManage'];
    $applicantUrl = $state['canSeeApplicant'] && $application && Route::has('hr.recruitment.applicants.show')
        ? route('hr.recruitment.applicants.show', ['applicantId' => $application->applicant_id]) : null;
    $requiredByAttendee = $interview->requiredParticipants->groupBy('attendee_user_id');
@endphp

<div class="space-y-6" data-interview="{{ $interview->id }}">
    <x-ui.detail-header
        :back="\App\Support\BackUrl::for(route('hr.recruitment.interviews.index'))"
        :breadcrumbs="[['label' => __('hr_recruitment.hub.section')], ['label' => __('hr_recruitment.hub.titles.interviews'), 'url' => route('hr.recruitment.interviews.index')]]"
        :title="__($t.'types.'.$interview->interview_type).' · '.($application?->applicant?->full_name ?? '—')"
    >
        <x-slot:meta>
            <x-ui.enum-badge :case="$interview->status" :label="__($t.'statuses.'.$interview->status)" :tone="\Modules\Hr\Support\Recruitment\Labels::tone($interview->status)" />
            <span dir="auto">{{ $application?->vacancy?->position?->title ?? __('hr_recruitment.applicant_ui.no_vacancy') }}</span>
            @if ($applicantUrl)
                <a href="{{ $applicantUrl }}" wire:navigate class="underline-offset-4 hover:underline">{{ __($t.'open_applicant') }}</a>
            @endif
        </x-slot:meta>
        @if ($canManage && $interview->isOpen())
            <x-slot:actions>
                @if ($state['hasStarted'])
                    <x-ui.button tone="primary" wire:click="beginOutcome('{{ RecruitmentInterviewStatus::Completed }}')">{{ __($t.'actions.completed') }}</x-ui.button>
                    <x-ui.button tone="secondary" wire:click="beginOutcome('{{ RecruitmentInterviewStatus::NoShow }}')">{{ __($t.'actions.no_show') }}</x-ui.button>
                @endif
                <x-ui.button tone="secondary" icon="pencil-square" :href="route('hr.recruitment.interviews.edit', ['interviewId' => $interview->id])" wire:navigate>{{ __($t.'actions.edit') }}</x-ui.button>
                <x-ui.button tone="secondary" wire:click="beginCancel">{{ __($t.'actions.cancel') }}</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.detail-header>

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0 space-y-6">
            <x-ui.panel :title="__($t.'details')">
                <dl class="grid gap-4 p-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="{{ $muted }}">{{ __($t.'when') }}</dt>
                        <dd class="font-medium">
                            {{ $starts->translatedFormat('l j F Y') }} · <span class="tabular-nums">{{ $starts->format('H:i') }}@if ($ends) – {{ $ends->format('H:i') }}@endif</span>
                            <span class="block text-xs font-normal {{ $muted }}">{{ $timezone }} (UTC{{ $starts->format('P') }})</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="{{ $muted }}">{{ __($t.'location') }}</dt>
                        <dd class="font-medium">
                            {{ __($t.'location_types.'.$interview->location_type) }}
                            @if ($interview->location_value)
                                <span class="block font-normal wrap-break-word" dir="auto">{{ $interview->location_value }}</span>
                            @endif
                            @if ($interview->location_type === \Modules\Hr\Enums\RecruitmentInterviewLocationType::Online)
                                @if ($state['meetingUrl'] && $interview->isOpen())
                                    <x-ui.button size="sm" tone="secondary" icon="arrow-top-right-on-square" class="mt-2" :href="$state['meetingUrl']" target="_blank" rel="noopener noreferrer">{{ __($t.'join_meeting') }}</x-ui.button>
                                    <span class="mt-1 block text-xs font-normal break-all {{ $muted }}" dir="ltr">{{ $state['meetingUrl'] }}</span>
                                @elseif ($state['meetingUrl'])
                                    <span class="mt-1 block text-xs font-normal break-all {{ $muted }}" dir="ltr">{{ $state['meetingUrl'] }}</span>
                                    <span class="block text-xs font-normal {{ $muted }}">{{ __($t.'meeting_over') }}</span>
                                @else
                                    <span class="block text-xs font-normal {{ $muted }}">{{ __($t.'meeting_url_hidden') }}</span>
                                @endif
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="{{ $muted }}">{{ __($t.'scheduled_by') }}</dt>
                        <dd class="font-medium">{{ $who($interview->created_by_user_id) }} · <span class="tabular-nums">{{ $at($interview->created_at) }}</span></dd>
                    </div>
                    @if ($interview->reschedule_count > 0)
                        <div>
                            <dt class="{{ $muted }}">{{ __($t.'reschedule_count') }}</dt>
                            <dd class="font-medium"><a href="#reschedule-history" class="tabular-nums underline underline-offset-4">{{ $interview->reschedule_count }}</a></dd>
                        </div>
                    @endif
                </dl>

                @if ($state['evaluationDue'])
                    <p class="mx-4 mb-4 flex flex-wrap items-center gap-x-3 gap-y-1 rounded-md bg-secondary-soft/60 p-3 text-sm dark:bg-surface-2">
                        <span>{{ __($t.'evaluation_due') }}</span>
                        <a href="#my-evaluation" class="font-semibold underline underline-offset-4">{{ __($t.'evaluation_due_link') }}</a>
                    </p>
                @endif

                @if ($interview->status === RecruitmentInterviewStatus::Cancelled)
                    @php $reasonKey = $t.'cancellation_reasons.'.$interview->cancellation_reason; @endphp
                    <div class="mx-4 mb-4 rounded-md border border-panel-border p-3 text-sm">
                        <p class="font-medium">{{ __($t.'cancelled_on', ['date' => $at($interview->cancelled_at), 'by' => $interview->cancelled_by_user_id ? $who($interview->cancelled_by_user_id) : __($t.'system')]) }}</p>
                        <p class="mt-1">{{ __($t.'cancel_reason') }}: {{ Lang::has($reasonKey) ? __($reasonKey) : $interview->cancellation_reason }}</p>
                        @if ($canManage && $interview->cancellation_note)
                            <p class="mt-1 whitespace-pre-line {{ $muted }}" dir="auto">{{ $interview->cancellation_note }}</p>
                        @endif
                    </div>
                @elseif (in_array($interview->status, [RecruitmentInterviewStatus::Completed, RecruitmentInterviewStatus::NoShow], true))
                    <div class="mx-4 mb-4 rounded-md border border-panel-border p-3 text-sm">
                        <p class="font-medium">{{ __($t.'outcome_on', ['date' => $at($interview->outcome_recorded_at), 'by' => $who($interview->outcome_recorded_by_user_id)]) }}</p>
                        @if ($canManage && $interview->outcome_note)
                            <p class="mt-1 whitespace-pre-line {{ $muted }}" dir="auto">{{ $interview->outcome_note }}</p>
                        @endif
                    </div>
                @endif

                @if ($canManage && $interview->isOpen() && ! $state['hasStarted'])
                    <p class="border-t border-panel-border px-4 py-2 text-xs {{ $muted }}">{{ __($t.'outcome_after_start') }}</p>
                @endif
            </x-ui.panel>

            @include('hr::pages.recruitment.interviews._show-evaluations')

            {{-- Reschedule history --}}
            @if ($interview->reschedules->isNotEmpty())
                <x-ui.panel id="reschedule-history" class="scroll-mt-24" :title="__($t.'history')">
                    <ol class="space-y-3 p-4 text-sm">
                        @foreach ($interview->reschedules as $change)
                            <li class="border-s-2 border-panel-border ps-3" wire:key="reschedule-{{ $change->id }}">
                                <p class="font-medium tabular-nums">
                                    {{ $at($change->from_scheduled_at) }} <span aria-hidden="true" class="rtl:inline-block rtl:rotate-180">→</span><span class="sr-only">{{ __($t.'changed_to') }}</span> {{ $at($change->to_scheduled_at) }}
                                    @if ($change->location_changed)
                                        <x-ui.enum-badge class="ms-1" case="place" :label="__($t.'location_changed')" />
                                    @endif
                                    @if ($change->reminder_already_sent)
                                        <x-ui.enum-badge class="ms-1" case="reminder" :label="__($t.'after_reminder')" />
                                    @endif
                                </p>
                                <p class="{{ $muted }}">{{ $who($change->rescheduled_by_user_id) }} · {{ $at($change->rescheduled_at) }}</p>
                                <p class="mt-1 whitespace-pre-line" dir="auto">{{ $change->reason }}</p>
                            </li>
                        @endforeach
                    </ol>
                </x-ui.panel>
            @endif
        </div>

        <aside class="space-y-4 xl:sticky xl:top-24">
            {{-- The interview's staff. Removed participants stay listed, struck through, so the history reads true. --}}
            <x-ui.panel :title="__($t.'participants')" :count="$interview->participants->whereNull('removed_at')->count()">
                @if ($interview->participants->isEmpty())
                    <p class="p-4 text-sm {{ $muted }}">{{ __($t.'no_participants') }}</p>
                @else
                    <ul class="divide-y divide-panel-border text-sm">
                        @foreach ($interview->participants as $participant)
                            <li @class(['px-4 py-2.5', $muted => $participant->removed_at]) wire:key="participant-{{ $participant->id }}">
                                <p>
                                    <span @class(['font-medium', 'line-through' => $participant->removed_at]) dir="auto">{{ $who($participant->user_id) }}</span>
                                    <span class="{{ $muted }}">· {{ __($t.'roles.'.$participant->role) }}</span>
                                </p>
                                <p class="mt-1 flex flex-wrap gap-1">
                                    @foreach ($requiredByAttendee->get($participant->user_id, collect()) as $required)
                                        <x-ui.enum-badge :case="$required->seat" :label="__($t.'required_seats.'.$required->seat).((int) $required->owner_user_id !== (int) $required->attendee_user_id ? ' · '.__($t.'on_behalf_of', ['name' => $who($required->owner_user_id)]) : '')" />
                                    @endforeach
                                    @unless (isset($state['active'][(int) $participant->user_id]))
                                        <x-ui.enum-badge case="inactive" :label="__($t.'inactive')" :tone="\App\Enums\StatusTone::Warn" />
                                    @endunless
                                </p>
                                @if ($participant->removed_at)
                                    <p class="text-xs">{{ __($t.'removed_on', ['date' => $at($participant->removed_at)]) }}</p>
                                @elseif ($canManage)
                                    <p class="text-xs {{ $muted }}">{{ __($t.'notification_statuses.'.$participant->notification_status) }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.panel>

            <x-ui.panel :title="__($t.'attachments.title')" :subtitle="__($t.'attachments.hint')">
                <ul class="divide-y divide-panel-border text-sm">
                    @forelse ($state['attachments'] as $media)
                        <li wire:key="interview-attachment-{{ $media->id }}" class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5">
                            <div class="min-w-0">
                                <p class="font-medium wrap-anywhere" dir="auto">{{ $media->getCustomProperty('original_name', $media->name) }}</p>
                                <p class="text-xs tabular-nums {{ $muted }}">{{ number_format($media->size / 1024, 1) }} KB · {{ $media->created_at?->format('Y-m-d H:i') }}</p>
                            </div>
                            <div class="flex items-center gap-1">
                                <x-ui.button tone="ghost" size="sm" icon="arrow-down-tray" class="size-8!" :href="route('hr.recruitment.interview-attachments.download', ['media' => $media->id])" :aria-label="__('hr_recruitment.hub.download')" />
                                @if ($canManage)
                                    <x-ui.button tone="ghost" size="sm" icon="trash" class="size-8!" wire:click="discardAttachment({{ $media->id }})" wire:confirm="{{ __($t.'attachments.remove_confirm') }}" :aria-label="__($t.'attachments.remove')" />
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-3 {{ $muted }}">{{ __($t.'attachments.none') }}</li>
                    @endforelse
                </ul>
                @if ($canManage)
                    <form wire:submit="uploadAttachment" class="space-y-3 border-t border-panel-border p-4">
                        <x-ui.form-file :label="__($t.'attachments.file')" wire:model="attachment" required
                            accept="audio/mpeg,audio/mp4,audio/wav,video/mp4,video/webm,application/pdf,image/jpeg,image/png"
                            :help="__($t.'attachments.formats')" />
                        <div class="flex justify-end">
                            <x-ui.button type="submit" tone="primary" size="sm" wire:loading.attr="disabled" wire:target="attachment,uploadAttachment">{{ __($t.'attachments.upload') }}</x-ui.button>
                        </div>
                    </form>
                @endif
            </x-ui.panel>

            {{-- Candidate emails: HR only. --}}
            @if ($canManage)
                <x-ui.panel :title="__($t.'candidate_emails')">
                    @if ($state['notices']->isEmpty())
                        <p class="p-4 text-sm {{ $muted }}">{{ __($t.'no_candidate_emails') }}</p>
                    @else
                        <ul class="divide-y divide-panel-border text-sm">
                            @foreach ($state['notices'] as $notice)
                                <li class="px-4 py-2.5" wire:key="notice-{{ $notice->id }}">
                                    <p class="font-medium">{{ __($t.'notice_kinds.'.$notice->kind) }}</p>
                                    <p class="text-xs {{ $muted }}">{{ __($t.'for_slot', ['slot' => $at($notice->scheduled_for)]) }} · {{ __($t.'notice_statuses.'.$notice->status) }} · {{ $at($notice->sent_at ?? $notice->failed_at ?? $notice->queued_at) }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.panel>
            @endif
        </aside>
    </div>

    {{-- Cancel / record the outcome. --}}
    <x-ui.modal
        wire:model="panelOpen"
        size="sm"
        close="dismissPanel"
        :title="$panel === null ? '' : ($panel === 'cancel' ? __($t.'cancel_title') : __($t.'outcome_title.'.$panel))"
        :description="$panel === 'cancel' ? __($t.'cancel_hint') : null"
        :submit="$panel === 'cancel' ? 'cancelInterview' : 'recordOutcome'"
    >
        @if ($panel !== null)
            <div class="space-y-4">
                @error('interview')
                    <p role="alert" class="rounded-md border border-status-critical/40 p-3 text-sm text-status-critical">{{ $message }}
                        <a href="{{ route('hr.recruitment.interviews.show', ['interviewId' => $interview->id]) }}" wire:navigate class="font-semibold underline underline-offset-4">{{ __($t.'reload') }}</a></p>
                @enderror
                @if ($panel === 'cancel')
                    <x-ui.form-select wire:model.live="cancelReason" :label="__($t.'cancel_reason')" required>
                        <option value="">{{ __('hr_recruitment.choose') }}</option>
                        @foreach (app(RecruitmentInterviewSchedulingService::class)->cancellationReasons() as $code)
                            <option value="{{ $code }}">{{ __($t.'cancellation_reasons.'.$code) }}</option>
                        @endforeach
                    </x-ui.form-select>
                    <x-ui.form-textarea wire:model="cancelNote" :label="__($t.'internal_note')" :help="__($t.'internal_note_hint')" :required="$cancelReason === RecruitmentInterviewSchedulingService::REASON_OTHER" rows="2" dir="auto" />
                    @unless ($state['hasStarted'])
                        <flux:checkbox wire:model="notifyCandidate" :label="__($t.'notify_candidate_cancel')" :description="__($t.'notify_candidate_hint')" />
                    @endunless
                @else
                    <x-ui.form-textarea wire:model="outcomeNote" :label="__($t.'internal_note')" :help="__($t.'outcome_note_hint')" rows="2" dir="auto" />
                    @error('outcome')<p class="text-sm text-status-critical">{{ $message }}</p>@enderror
                @endif
            </div>
        @endif

        <x-slot:actions>
            <x-ui.button type="submit" tone="primary" wire:loading.attr="disabled">{{ $panel === 'cancel' ? __($t.'confirm_cancel') : __($t.'confirm_outcome') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.modal>
</div>
