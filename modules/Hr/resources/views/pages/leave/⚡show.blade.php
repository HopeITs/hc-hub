<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Actions\Leave\DecideLeaveRequest;
use Modules\Hr\Actions\Leave\DeleteLeaveRequest;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Livewire\Concerns\Leave\ManagesLeaveRequestForm;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Support\Leave\LeaveContext;
use Modules\Hr\Support\Leave\LeaveDecisionAlreadyMade;
use Modules\Hr\Support\Leave\LeaveRequestNextStep;
use Modules\Hr\Support\Leave\LeaveRequestReview;

/*
 * One leave request: who, what, when, how much, where it stands and HR's
 * notes. The direct manager (or HR on their behalf) accepts or rejects it
 * here; HR marks a decision as seen or objects to it; the owner edits or
 * withdraws it while pending.
 */
new #[Layout('layouts::desk'), Title('hr_leave.titles.request')] class extends Component {
    use ManagesLeaveRequestForm;

    #[Locked]
    public int $leaveRequestId;

    public bool $showDecideModal = false;

    public string $decision = '';

    public string $rejectReason = '';

    public bool $showReviewModal = false;

    /** 'seen' or 'object' */
    #[Locked]
    public string $reviewKind = 'seen';

    public string $reviewNote = '';

    public bool $showDeleteModal = false;

    public function mount(int $leaveRequest): void
    {
        $this->leaveRequestId = $leaveRequest;
        Gate::authorize('view', $this->leaveRequest);
    }

    #[Computed]
    public function leaveRequest(): LeaveRequest
    {
        $leaveRequest = LeaveContext::request($this->leaveRequestId);
        $leaveRequest->load([
            'notes' => fn ($query) => $query->select(['id', 'leave_request_id', 'author_employee_id', 'author_role', 'kind', 'body', 'created_at'])->oldest('id'),
            'notes.author:id,first_name_en,first_name_ar,last_name_en,last_name_ar',
            'alternativeEmployee:id,first_name_en,first_name_ar,last_name_en,last_name_ar',
        ]);

        return $leaveRequest;
    }

    public function openDecide(string $decision): void
    {
        Gate::authorize('decide', $this->leaveRequest);

        $this->resetValidation();
        $this->decision = $decision === 'reject' ? 'reject' : 'accept';
        $this->rejectReason = '';
        $this->showDecideModal = true;
    }

    public function decide(): void
    {
        $status = $this->decision === 'reject' ? LeaveRequestStatus::Rejected->value : LeaveRequestStatus::Accepted->value;

        try {
            app(DecideLeaveRequest::class)->handle(Auth::user(), $this->leaveRequestId, $status, $this->rejectReason);
        } catch (LeaveDecisionAlreadyMade $exception) {
            $this->closeDecide();
            unset($this->leaveRequest);
            Flux::toast(variant: 'warning', text: app(LeaveRequestNextStep::class)->alreadyDecided($exception->leaveRequest, Auth::user()));

            return;
        }

        $this->closeDecide();
        unset($this->leaveRequest);

        Flux::toast(variant: 'success', text: $status === LeaveRequestStatus::Accepted->value ? __('hr_leave.ui.accepted_toast') : __('hr_leave.ui.rejected_toast'));
    }

    public function closeDecide(): void
    {
        $this->showDecideModal = false;
        $this->decision = '';
        $this->rejectReason = '';
    }

    public function openReview(string $kind): void
    {
        $this->reviewKind = $kind === 'object' ? 'object' : 'seen';
        Gate::authorize($this->reviewKind === 'object' ? 'object' : 'review', $this->leaveRequest);

        $this->resetValidation();
        $this->reviewNote = '';
        $this->showReviewModal = true;
    }

    public function review(): void
    {
        $review = app(LeaveRequestReview::class);
        $note = trim($this->reviewNote) === '' ? null : $this->reviewNote;

        try {
            $this->reviewKind === 'object'
                ? $review->object($this->leaveRequestId, Auth::user(), $note)
                : $review->markSeen($this->leaveRequestId, Auth::user(), $note);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            throw \Illuminate\Validation\ValidationException::withMessages(['reviewNote' => collect($exception->errors())->flatten()->all()]);
        }

        $kind = $this->reviewKind;
        $this->closeReview();
        unset($this->leaveRequest);

        Flux::toast(variant: 'success', text: $kind === 'object' ? __('hr_leave.ui.objected_toast') : __('hr_leave.ui.seen_toast'));
    }

    public function closeReview(): void
    {
        $this->showReviewModal = false;
        $this->reviewNote = '';
    }

    public function confirmDelete(): void
    {
        Gate::authorize('delete', $this->leaveRequest);
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $owner = (int) $this->leaveRequest->leaveBalance?->contract?->employee_id === (int) Auth::user()?->employee_id;

        app(DeleteLeaveRequest::class)->handle(Auth::user(), $this->leaveRequestId);

        Flux::toast(variant: 'success', text: __('hr_leave.ui.deleted'));

        $this->redirectRoute($owner ? 'hr.leave.index' : 'hr.leave.team', navigate: true);
    }

    protected function afterRequestSaved(): void
    {
        unset($this->leaveRequest);
    }
};
?>

@php
    $leaveRequest = $this->leaveRequest;
    $user = auth()->user();
    $contract = $leaveRequest->leaveBalance?->contract;
    $employee = $contract?->employee;
    $perDay = $contract === null ? null : app(\Modules\Hr\Support\Leave\LeaveRequestPricing::class)->hoursPerDayFor($contract);
    $nextStep = app(\Modules\Hr\Support\Leave\LeaveRequestNextStep::class);
    $isPending = $leaveRequest->status === \Modules\Hr\Enums\LeaveRequestStatus::Pending->value;
    $isMine = $user->employee_id !== null && (int) $contract?->employee_id === (int) $user->employee_id;
    $canDecide = $user->can('decide', $leaveRequest);
    $canReview = $user->can('review', $leaveRequest);
    $canObject = $user->can('object', $leaveRequest);
    $canEdit = $user->can('update', $leaveRequest);
    $canDelete = $user->can('delete', $leaveRequest);
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $back = $isMine ? route('hr.leave.index') : route('hr.leave.team');
@endphp

<div class="flex flex-col gap-6" data-hr-leave-request="{{ $leaveRequest->id }}">
    <x-ui.detail-header :back="$back" :title="__('hr_leave.ui.request_of', ['name' => (string) $employee?->name])" :subtitle="$leaveRequest->leaveBalance?->leaveType?->name">
        <x-slot:actions>
            @if ($canDecide)
                <x-ui.button tone="primary" icon="check" wire:click="openDecide('accept')" data-test="accept-leave">{{ __('hr_leave.ui.accept') }}</x-ui.button>
                <x-ui.button tone="secondary" icon="x-mark" wire:click="openDecide('reject')" data-test="reject-leave">{{ __('hr_leave.ui.reject') }}</x-ui.button>
            @endif
            @if ($canReview)
                <x-ui.button tone="secondary" icon="eye" wire:click="openReview('seen')" data-test="review-leave">{{ __('hr_leave.ui.mark_seen') }}</x-ui.button>
            @endif
            @if ($canObject)
                <x-ui.button tone="secondary" icon="arrow-uturn-left" wire:click="openReview('object')" data-test="object-leave">{{ __('hr_leave.ui.object') }}</x-ui.button>
            @endif
            @if ($canEdit)
                <x-ui.button tone="secondary" icon="pencil-square" wire:click="openEditForm({{ $leaveRequest->id }})" data-test="edit-leave">{{ __('hr_leave.ui.edit') }}</x-ui.button>
            @endif
            @if ($canDelete)
                <x-ui.button tone="ghost" icon="trash" class="text-status-critical!" wire:click="confirmDelete" data-test="delete-leave">{{ $isMine && $isPending ? __('hr_leave.ui.withdraw') : __('common.delete') }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.detail-header>

    <div class="rounded-lg border border-secondary-soft bg-secondary-soft/40 px-4 py-3 text-sm dark:border-line dark:bg-surface-2" data-next-step>
        <x-hr::leave.status :status="$leaveRequest->status" class="me-2" />
        {{ $nextStep->describe($leaveRequest, $user) }}
    </div>

    <x-ui.section :title="__('hr_leave.ui.details')" key="leave-details">
        <dl class="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="type-label {{ $muted }}">{{ __('hr_leave.fields.employee') }}</dt>
                <dd dir="auto">{{ $employee?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="type-label {{ $muted }}">{{ __('hr_leave.fields.leave_balance_id') }}</dt>
                <dd>{{ $leaveRequest->leaveBalance?->leaveType?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="type-label {{ $muted }}">{{ __('hr_leave.ui.period') }}</dt>
                <dd class="tabular-nums">{{ \Modules\Hr\Support\Leave\LeaveFormat::period($leaveRequest) }}</dd>
            </div>
            <div>
                <dt class="type-label {{ $muted }}">{{ __('hr_leave.ui.duration') }}</dt>
                <dd>{{ \Modules\Hr\Support\Leave\LeaveFormat::duration($leaveRequest, $perDay) }}</dd>
            </div>
            <div>
                <dt class="type-label {{ $muted }}">{{ __('hr_leave.fields.alternative_employee') }}</dt>
                <dd dir="auto">{{ $leaveRequest->alternativeEmployee?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="type-label {{ $muted }}">{{ __('hr_leave.ui.filed_on') }}</dt>
                <dd class="tabular-nums">{{ $leaveRequest->created_at?->format('d-m-Y, H:i') ?? '—' }}</dd>
            </div>
            @if ($leaveRequest->is_exceptional)
                <div>
                    <dt class="type-label {{ $muted }}">{{ __('hr_leave.ui.exceptional') }}</dt>
                    <dd>{{ __('hr_leave.ui.yes') }}</dd>
                </div>
            @endif
            <div class="sm:col-span-2 lg:col-span-3">
                <dt class="type-label {{ $muted }}">{{ __('hr_leave.fields.reason') }}</dt>
                <dd class="whitespace-pre-line" dir="auto">{{ $leaveRequest->reason ?: '—' }}</dd>
            </div>
            @if (! $isPending)
                <div class="sm:col-span-2 lg:col-span-3">
                    <dt class="type-label {{ $muted }}">{{ __('hr_leave.ui.decision') }}</dt>
                    <dd>{{ $nextStep->decisionSummary($leaveRequest) }}</dd>
                </div>
            @endif
            @if ($leaveRequest->reject_reason)
                <div class="sm:col-span-2 lg:col-span-3">
                    <dt class="type-label {{ $muted }}">{{ __('hr_leave.fields.reject_reason') }}</dt>
                    <dd class="whitespace-pre-line" dir="auto">{{ $leaveRequest->reject_reason }}</dd>
                </div>
            @endif
        </dl>
    </x-ui.section>

    <x-ui.section :title="__('hr_leave.ui.notes')" key="leave-notes">
        @if ($leaveRequest->notes->isEmpty())
            <p class="mt-3 text-sm {{ $muted }}">{{ __('hr_leave.ui.no_notes') }}</p>
        @else
            <ul class="mt-3 flex flex-col gap-3">
                @foreach ($leaveRequest->notes as $note)
                    <li class="rounded-lg border border-secondary-soft px-3 py-2 text-sm dark:border-line" wire:key="leave-note-{{ $note->id }}">
                        <p class="text-xs {{ $muted }}">
                            <span dir="auto">{{ $note->author?->name ?? '—' }}</span>
                            · {{ __('hr_leave.ui.note_kinds.'.$note->kind) }}
                            · <span class="tabular-nums">{{ $note->created_at?->format('d-m-Y, H:i') }}</span>
                        </p>
                        <p class="mt-1 whitespace-pre-line" dir="auto">{{ $note->body }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.section>

    @include('hr::pages.leave._request-form-modal')

    <x-ui.modal wire:model="showDecideModal" size="sm" :title="$decision === 'reject' ? __('hr_leave.ui.reject_title') : __('hr_leave.ui.accept_title')" submit="decide" close="closeDecide">
        @if ($showDecideModal)
            <div class="grid gap-y-3">
                <p class="text-sm">{{ __('hr_leave.ui.decide_summary', ['name' => (string) $employee?->name, 'period' => \Modules\Hr\Support\Leave\LeaveFormat::period($leaveRequest), 'duration' => \Modules\Hr\Support\Leave\LeaveFormat::duration($leaveRequest, $perDay)]) }}</p>
                @if ($decision === 'reject')
                    <x-ui.form-textarea wire:model="rejectReason" :label="__('hr_leave.fields.reject_reason')" required maxlength="255" />
                @endif
                @error('status')
                    <p class="form-error">{{ $message }}</p>
                @enderror
                @error('reject_reason')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
        @endif
        <x-slot:actions>
            <x-ui.button type="submit" tone="primary" data-test="confirm-decision">{{ $decision === 'reject' ? __('hr_leave.ui.reject') : __('hr_leave.ui.accept') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.modal>

    <x-ui.modal wire:model="showReviewModal" size="sm" :title="$reviewKind === 'object' ? __('hr_leave.ui.object_title') : __('hr_leave.ui.seen_title')" :description="$reviewKind === 'object' ? __('hr_leave.ui.object_help') : __('hr_leave.ui.seen_help')" submit="review" close="closeReview">
        @if ($showReviewModal)
            <x-ui.form-textarea wire:model="reviewNote" :label="__('hr_leave.ui.note')" :required="$reviewKind === 'object'" maxlength="1000" />
        @endif
        <x-slot:actions>
            <x-ui.button type="submit" tone="primary" data-test="confirm-review">{{ $reviewKind === 'object' ? __('hr_leave.ui.object') : __('hr_leave.ui.mark_seen') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.modal>

    <x-ui.confirm-modal
        wire:model="showDeleteModal"
        :title="__('hr_leave.ui.delete_title')"
        :message="__('hr_leave.ui.delete_message')"
        confirm="delete"
        :confirm-label="__('common.delete')"
        confirm-test="confirm-delete-leave"
    />
</div>
