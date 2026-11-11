<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Actions\Recruitment\RecruitmentApprovalService;
use Modules\Hr\Actions\Recruitment\RecruitmentApprovalTrack;
use Modules\Hr\Actions\Recruitment\RecruitmentEditableFields;
use Modules\Hr\Actions\Recruitment\RecruitmentPendingApprovalQuery;
use Modules\Hr\Actions\Recruitment\RecruitmentRequestApplicationService;
use Modules\Hr\Actions\Recruitment\RecruitmentRequestSubmissionService;
use Modules\Hr\Enums\RecruitmentEventType;
use Modules\Hr\Enums\RecruitmentRequestStatus;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Livewire\Concerns\Recruitment\SubmitsRecruitmentRequests;
use Modules\Core\Models\Center;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\HiringCampaign;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\Position;
use Modules\Hr\Models\RecruitmentRequest;
use Modules\Hr\Models\RecruitmentRequestVersion;
use Modules\Hr\Models\RecruitmentWorkflowInstanceStep;
use Modules\Hr\Models\Vacancy;
use Modules\Core\Models\Program;
use Modules\Hr\Support\Recruitment\Labels;

new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.request')] class extends Component {
    use AuthorizesRecruitment;
    use SubmitsRecruitmentRequests;

    #[Locked]
    public int $requestId;

    public ?int $versionId = null;

    /** The reason, or what needs changing: mandatory for both (D60). */
    public string $decisionComment = '';

    /** @var array<int, string> the fields a request for changes unlocks */
    public array $requestedFields = [];

    public bool $askingForChanges = false;

    public bool $rejecting = false;

    public bool $showCancelModal = false;

    public bool $showExecuteModal = false;

    public function mount(int $requestId): void
    {
        $this->requirePermission('recruitment-requests.view');
        $this->requestId = $requestId;
        $this->resolve();
    }

    #[Computed]
    public function request(): RecruitmentRequest
    {
        Gate::authorize('viewAny', RecruitmentRequest::class);

        $request = RecruitmentRequest::query()->visibleTo($this->viewer())
            ->with([
                'requester:id,email,employee_id',
                'scopeCity:id,name_en,name_ar',
                'targetPosition:id,title_en,title_ar',
                'vacancy',
                'vacancy.position:id,title_en,title_ar',
                'versions' => fn ($query) => $query->orderByDesc('version_no'),
                'comments' => fn ($query) => $query->with('user:id,email,employee_id')->orderBy('created_at')->orderBy('id'),
                'externalConfirmations.recordedBy:id,email,employee_id',
            ])
            ->findOrFail($this->requestId);

        Gate::authorize('view', $request);

        return $request;
    }

    #[Computed]
    public function version(): ?RecruitmentRequestVersion
    {
        $versions = $this->request->versions;
        $version = $this->versionId === null ? $versions->first() : $versions->firstWhere('id', $this->versionId);
        $version?->load('positionDetail');

        return $version;
    }

    /** The step this user is the assigned approver of, if the request waits on one. */
    #[Computed]
    public function pendingStep(): ?RecruitmentWorkflowInstanceStep
    {
        return app(RecruitmentPendingApprovalQuery::class)
            ->forUser($this->viewer())
            ->where('pending_requests.id', $this->requestId)
            ->first()
            ?->loadMissing(['workflowStep', 'workflowInstance.requestVersion']);
    }

    /** @return array<string, string> the draft's ids named, for a draft that has no version yet */
    #[Computed]
    public function draftContext(): array
    {
        $request = $this->request;

        if ((int) $request->status !== RecruitmentRequestStatus::Draft->value) {
            return [];
        }

        $context = [];

        foreach ([
            'direct_manager_id' => ['parent_position', Position::class, 'title'],
            'program_id' => ['program', Program::class, 'name'],
            'center_id' => ['center', Center::class, 'name'],
            'department_id' => ['department', Department::class, 'name'],
            'job_level_id' => ['job_level', JobLevel::class, 'name'],
            'contract_type_id' => ['contract_type', ContractType::class, 'name'],
        ] as $field => [$label, $model, $attribute]) {
            $id = $request->draft_payload_json[$field] ?? null;

            if (is_numeric($id)) {
                $context[$label] = $model::query()->find((int) $id)?->{$attribute} ?? '—';
            }
        }

        return $context;
    }

    /**
     * Requests on this same position that were rejected before (D61), read
     * through the visibility boundary like everything else. Shown, not blocking.
     */
    #[Computed]
    public function previousRejections(): Collection
    {
        $request = $this->request;

        if ($request->target_position_id === null) {
            return collect();
        }

        return RecruitmentRequest::query()
            ->visibleTo($this->viewer())
            ->whereKeyNot($request->getKey())
            ->where('target_position_id', $request->target_position_id)
            ->where('status', RecruitmentRequestStatus::Rejected->value)
            ->with(['requester:id,email,employee_id', 'events' => fn ($events) => $events
                ->where('event_type', RecruitmentEventType::StepRejected->value)
                ->with('actor:id,email,employee_id')
                ->latest('created_at')])
            ->latest('updated_at')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function track(): array
    {
        return app(RecruitmentApprovalTrack::class)->for($this->request);
    }

    /** @return array<int, string> */
    #[Computed]
    public function selectableFields(): array
    {
        $step = $this->pendingStep;

        return $step === null ? [] : app(RecruitmentEditableFields::class)->selectable($this->request, (int) $step->workflowStep?->step_key);
    }

    /** @return array<int, string> */
    #[Computed]
    public function unlockedFields(): array
    {
        return app(RecruitmentEditableFields::class)->requestedByApprover($this->request);
    }

    #[Computed]
    public function canCreateCampaign(): bool
    {
        $request = $this->request;
        $vacancy = $request->vacancy;

        return (int) $request->status === RecruitmentRequestStatus::Applied->value
            && $vacancy !== null
            && $vacancy->status === Vacancy::OPEN
            && $vacancy->closed_at === null
            && $vacancy->open_at?->isPast()
            && $vacancy->filled_count < $vacancy->needed_count
            && $this->may('recruitment-campaigns.manage')
            && Gate::allows('create', [HiringCampaign::class, $vacancy]);
    }

    public function submit(RecruitmentRequestSubmissionService $submissions): void
    {
        $request = $this->resolve();
        Gate::authorize('submit', $request);
        $this->submitRecruitmentRequest($request, $submissions);
        $this->refreshRequest();
    }

    public function cancelRequest(RecruitmentRequestSubmissionService $submissions): void
    {
        $this->showCancelModal = false;

        if ($this->attempt(fn () => $submissions->cancel($this->viewer(), $this->resolve()), __('hr_recruitment.cancelled'))) {
            $this->refreshRequest();
        }
    }

    /** Carry out an approved request before its effective date: HR's own decision. */
    public function executeNow(RecruitmentRequestApplicationService $applications): void
    {
        $this->showExecuteModal = false;
        $request = $this->resolve();
        Gate::authorize('execute', $request);

        $request = $applications->apply($request, $this->viewer(), force: true);
        $applied = (int) $request->status === RecruitmentRequestStatus::Applied->value;
        $this->toast(__($applied ? 'hr_recruitment.executed' : 'hr_recruitment.execution_failed'), $applied);
        $this->refreshRequest();
    }

    public function askForChanges(): void
    {
        $this->resetErrorBag();
        $this->askingForChanges = true;
    }

    public function askToReject(): void
    {
        $this->resetErrorBag();
        $this->rejecting = true;
    }

    public function cancelChangeRequest(): void
    {
        $this->resetErrorBag();
        $this->reset(['askingForChanges', 'rejecting', 'requestedFields', 'decisionComment']);
    }

    /**
     * Decide the step this user is waited on for. The step is resolved here,
     * never taken from the page; the version number comes from the page on
     * purpose, since it is what the service compares against (check 6).
     */
    public function decide(string $action, RecruitmentApprovalService $approvals): void
    {
        $this->resolve();
        unset($this->pendingStep);
        $step = $this->pendingStep;

        if ($step === null) {
            $this->addError('step', __('hr_recruitment.step_is_not_current'));

            return;
        }

        $versionNo = (int) $step->workflowInstance?->requestVersion?->version_no;
        $comment = trim($this->decisionComment);

        $done = $this->attempt(fn () => match ($action) {
            'approve' => $approvals->approve($this->viewer(), $step, $versionNo, $comment ?: null),
            'reject' => $approvals->reject($this->viewer(), $step, $versionNo, $comment),
            'request_changes' => $approvals->requestChanges($this->viewer(), $step, $versionNo, $comment, $this->requestedFields),
            default => throw new InvalidArgumentException('Unknown decision.'),
        }, __('hr_recruitment.decided'));

        if ($done) {
            $this->cancelChangeRequest();
            $this->refreshRequest();
        }
    }

    private function refreshRequest(): void
    {
        unset($this->request, $this->version, $this->pendingStep, $this->track, $this->selectableFields, $this->unlockedFields, $this->canCreateCampaign, $this->draftContext);
    }

    private function resolve(): RecruitmentRequest
    {
        $request = RecruitmentRequest::query()->visibleTo($this->viewer())->findOrFail($this->requestId);
        Gate::authorize('view', $request);

        return $request;
    }
};
?>

@php
    $request = $this->request;
    $status = (int) $request->status;
    $version = $this->version;
    $pendingStep = $this->pendingStep;
    $applications = app(\Modules\Hr\Actions\Recruitment\RecruitmentRequestApplicationService::class);
    $awaitingExecution = $applications->isAwaitingEffectiveDate($request);
    $effectiveDate = $applications->effectiveDate($request);
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $date = fn ($value) => $value?->translatedFormat(__('formats.datetime')) ?? '—';
@endphp

<div class="space-y-6" data-recruitment-request="{{ $request->id }}">
    <x-ui.detail-header
        :back="\App\Support\BackUrl::for(route('hr.recruitment.requests.index'))"
        :breadcrumbs="[['label' => __('hr_recruitment.hub.section')], ['label' => __('hr_recruitment.hub.titles.requests'), 'href' => route('hr.recruitment.requests.index')]]"
        :title="$request->request_number"
    >
        <x-slot:meta>
            <x-ui.enum-badge :case="RecruitmentRequestStatus::from($status)" :label="Labels::enum('RecruitmentRequestStatus', $status)" :tone="Labels::requestTone($status)" />
            <span dir="auto">{{ $request->targetPosition?->title ?? '—' }}</span>
            @if ($request->scopeCity)<span>{{ $request->scopeCity->name }}</span>@endif
            <span>{{ Labels::enum('RecruitmentRequestType', $request->request_type) }}</span>
        </x-slot:meta>
        <x-slot:actions>
            @can('cancel', $request)
                <flux:button variant="danger" icon="x-mark" wire:click="$set('showCancelModal', true)">{{ __('hr_recruitment.cancel_request') }}</flux:button>
            @endcan
            @can('update', $request)
                <x-ui.button tone="secondary" icon="pencil-square" :href="route('hr.recruitment.requests.edit', ['requestId' => $request->id])" wire:navigate>{{ __('hr_recruitment.edit') }}</x-ui.button>
            @endcan
            @can('submit', $request)
                <x-ui.button tone="primary" icon="paper-airplane" wire:click="submit" wire:loading.attr="disabled" wire:target="submit">{{ __('hr_recruitment.submit') }}</x-ui.button>
            @endcan
            @if ($this->canCreateCampaign)
                <x-ui.button tone="primary" icon="plus" :href="route('hr.recruitment.campaigns.create', ['vacancyId' => $request->vacancy->id])" wire:navigate>{{ __('hr_recruitment.campaign.create') }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.detail-header>

    @if ($pendingStep)
        {{-- Only for the approver this step waits on; the service asks again, so this is a convenience, not a guard. --}}
        <x-ui.panel :clip="false" class="p-4" data-decision-panel>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold">{{ __('hr_recruitment.your_decision') }}</h2>
                    <p class="text-sm {{ $muted }}">{{ Labels::enum('WorkflowStepKey', (int) $pendingStep->workflowStep?->step_key) }}</p>
                </div>
                @if (! $askingForChanges && ! $rejecting)
                    <div class="flex flex-wrap gap-2">
                        <flux:button variant="danger" wire:click="askToReject">{{ __('hr_recruitment.reject_action') }}</flux:button>
                        <x-ui.button tone="secondary" wire:click="askForChanges">{{ __('hr_recruitment.request_changes') }}</x-ui.button>
                        <x-ui.button tone="primary" icon="check" wire:click="decide('approve')" wire:loading.attr="disabled" wire:target="decide">{{ __('hr_recruitment.approve') }}</x-ui.button>
                    </div>
                @endif
            </div>

            @error('step')<p class="mt-2 text-sm text-status-critical" role="alert">{{ $message }}</p>@enderror
            @error('version')<p class="mt-2 text-sm text-status-critical" role="alert">{{ $message }}</p>@enderror

            @if ($rejecting)
                <div class="mt-4 space-y-3">
                    <x-ui.form-textarea wire:model="decisionComment" :label="__('hr_recruitment.rejection_reason')" rows="3" required dir="auto" />
                    @error('comment')<p class="text-sm text-status-critical">{{ $message }}</p>@enderror
                    <div class="flex flex-wrap gap-2">
                        <flux:button variant="danger" wire:click="decide('reject')" wire:confirm="{{ __('hr_recruitment.confirm_reject') }}" wire:loading.attr="disabled" wire:target="decide">{{ __('hr_recruitment.reject_action') }}</flux:button>
                        <x-ui.button tone="ghost" wire:click="cancelChangeRequest">{{ __('hr_recruitment.back_to_decisions') }}</x-ui.button>
                    </div>
                </div>
            @elseif ($askingForChanges)
                <div class="mt-4 space-y-3">
                    <fieldset>
                        <legend class="text-sm font-semibold">{{ __('hr_recruitment.fields_to_unlock') }}</legend>
                        <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.fields_to_unlock_hint') }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($this->selectableFields as $unlockable)
                                <flux:checkbox wire:model="requestedFields" value="{{ $unlockable }}" :label="\Modules\Hr\Support\Recruitment\Labels::field($unlockable)" wire:key="unlock-{{ $unlockable }}" />
                            @endforeach
                        </div>
                        @error('fields')<p class="mt-1 text-sm text-status-critical">{{ $message }}</p>@enderror
                    </fieldset>
                    <x-ui.form-textarea wire:model="decisionComment" :label="__('hr_recruitment.decision_comment')" rows="3" required dir="auto" />
                    @error('comment')<p class="text-sm text-status-critical">{{ $message }}</p>@enderror
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button tone="primary" wire:click="decide('request_changes')" wire:loading.attr="disabled" wire:target="decide">{{ __('hr_recruitment.send_change_request') }}</x-ui.button>
                        <x-ui.button tone="ghost" wire:click="cancelChangeRequest">{{ __('hr_recruitment.back_to_decisions') }}</x-ui.button>
                    </div>
                </div>
            @endif
        </x-ui.panel>
    @endif

    @if ($this->unlockedFields && $status === RecruitmentRequestStatus::ChangesRequested->value)
        <x-ui.panel :clip="false" class="p-4">
            <h2 class="text-sm font-semibold">{{ __('hr_recruitment.fields_you_may_edit') }}</h2>
            <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.fields_you_may_edit_hint') }}</p>
            <ul class="mt-2 flex flex-wrap gap-1.5">
                @foreach ($this->unlockedFields as $unlocked)
                    <li class="rounded-md bg-secondary-soft px-2 py-0.5 text-sm dark:bg-surface-2">{{ \Modules\Hr\Support\Recruitment\Labels::field($unlocked) }}</li>
                @endforeach
            </ul>
        </x-ui.panel>
    @endif

    @if ($awaitingExecution)
        <x-ui.panel :clip="false" class="flex flex-wrap items-center justify-between gap-3 p-4">
            <div>
                <h2 class="text-sm font-semibold">{{ __('hr_recruitment.awaiting_execution') }}</h2>
                @if ($effectiveDate)
                    <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.effective_on', ['date' => $effectiveDate->translatedFormat(__('formats.date'))]) }}</p>
                @endif
            </div>
            @if ($this->may('recruitment-requests.manage'))
                <x-ui.button tone="secondary" wire:click="$set('showExecuteModal', true)">{{ __('hr_recruitment.execute_now') }}</x-ui.button>
            @endif
        </x-ui.panel>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="min-w-0 space-y-6">
            <x-hr::recruitment.approval-track :track="$this->track" />

            <x-hr::recruitment.request-content :request="$request" :version="$version" :draft-context="$this->draftContext" />

            <x-ui.panel :title="__('hr_recruitment.comments')" :count="$request->comments->count()">
                <div class="space-y-3 p-4">
                    @forelse ($request->comments as $comment)
                        <article wire:key="comment-{{ $comment->id }}">
                            <p class="text-sm"><span class="font-semibold" dir="auto">{{ $comment->user?->name ?? '—' }}</span> <span class="{{ $muted }}">· {{ $date($comment->created_at) }}</span></p>
                            <p class="mt-1 whitespace-pre-wrap rounded-md bg-secondary-soft/50 px-3 py-2 text-sm dark:bg-surface-2" dir="auto">{{ $comment->body }}</p>
                        </article>
                    @empty
                        <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.no_comments') }}</p>
                    @endforelse
                </div>
            </x-ui.panel>

            @if ((int) $request->mode === \Modules\Hr\Enums\RecruitmentRequestMode::DirectHr->value)
                <x-ui.panel :title="__('hr_recruitment.external_confirmations')">
                    <div class="p-4">
                        <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.external_description') }}</p>
                        @if (filled($request->direct_open_reason))
                            <p class="mt-3 whitespace-pre-wrap rounded-md bg-secondary-soft/50 px-3 py-2 text-sm dark:bg-surface-2" dir="auto">{{ $request->direct_open_reason }}</p>
                        @endif
                        <ul class="mt-3 divide-y divide-panel-border">
                            @forelse ($request->externalConfirmations as $confirmation)
                                <li class="py-2" wire:key="confirmation-{{ $confirmation->id }}">
                                    <p class="text-sm font-semibold">{{ Labels::enum('ExternalConfirmationDecision', (int) $confirmation->decision) }}</p>
                                    @if ((int) $confirmation->decision === \Modules\Hr\Enums\ExternalConfirmationDecision::NotApplicable->value)
                                        <p class="whitespace-pre-wrap text-sm" dir="auto">{{ $confirmation->not_applicable_reason }}</p>
                                    @else
                                        <p class="text-sm" dir="auto">{{ $confirmation->confirmer_name }} · {{ Labels::enum('ExternalConfirmationMethod', (int) $confirmation->method) }}</p>
                                    @endif
                                    <p class="text-xs {{ $muted }}">{{ __('hr_recruitment.recorded_by') }}: {{ $confirmation->recordedBy?->name ?? '—' }}</p>
                                </li>
                            @empty
                                <li class="py-2 text-sm {{ $muted }}">{{ __('hr_recruitment.no_confirmations') }}</li>
                            @endforelse
                        </ul>
                    </div>
                </x-ui.panel>
            @endif
        </div>

        <aside class="space-y-4 xl:sticky xl:top-24">
            <x-ui.panel :title="__('hr_recruitment.details')">
                <dl class="divide-y divide-panel-border px-4 text-sm">
                    @foreach ([
                        'type' => Labels::enum('RecruitmentRequestType', $request->request_type),
                        'mode' => Labels::enum('RecruitmentRequestMode', $request->mode),
                        'city' => $request->scopeCity?->name ?? '—',
                        'position' => $request->targetPosition?->title ?? '—',
                        'requester' => $request->requester?->name ?? '—',
                        'created_at' => $date($request->created_at),
                        'submitted_at' => $date($request->submitted_at),
                    ] as $label => $factValue)
                        <div class="flex items-baseline justify-between gap-4 py-2">
                            <dt class="shrink-0 {{ $muted }}">{{ __('hr_recruitment.'.$label) }}</dt>
                            <dd class="min-w-0 text-end font-medium" dir="auto">{{ $factValue }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-ui.panel>

            @if ($this->previousRejections->isNotEmpty())
                <x-ui.panel :title="__('hr_recruitment.previous_rejections')" :count="$this->previousRejections->count()" role="note">
                    <div class="space-y-2 p-4">
                        <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.previous_rejections_help') }}</p>
                        @foreach ($this->previousRejections as $rejected)
                            @php
                                $decision = $rejected->events->first();
                                $when = $decision?->created_at ?? $rejected->updated_at;
                            @endphp
                            <div class="rounded-md bg-secondary-soft/50 p-3 text-sm dark:bg-surface-2" wire:key="rejection-{{ $rejected->id }}">
                                <div class="flex justify-between gap-2"><span class="font-semibold tabular-nums">{{ $rejected->request_number }}</span><span class="text-xs {{ $muted }}">{{ $when?->translatedFormat(__('formats.date')) }}</span></div>
                                <p class="text-xs {{ $muted }}" dir="auto">{{ $rejected->requester?->name ?? '—' }}@if ($decision?->actor) · {{ __('hr_recruitment.rejected_by') }}: {{ $decision->actor->name }}@endif</p>
                                <p class="mt-1 whitespace-pre-wrap" dir="auto">{{ filled($decision?->comment) ? $decision->comment : __('hr_recruitment.no_reason_recorded') }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-ui.panel>
            @endif
        </aside>
    </div>

    <x-ui.confirm-modal wire:model="showCancelModal" :title="__('hr_recruitment.cancel_request')" :message="__('hr_recruitment.confirm_cancel')" confirm="cancelRequest" :confirm-label="__('hr_recruitment.cancel_request')" />
    <x-ui.confirm-modal wire:model="showExecuteModal" tone="neutral" :title="__('hr_recruitment.execute_now')" :message="__('hr_recruitment.confirm_execute_now')" confirm="executeNow" :confirm-label="__('hr_recruitment.execute_now')" />
</div>
