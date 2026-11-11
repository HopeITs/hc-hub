<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Actions\Recruitment\RecruitmentInterviewSchedulingService;
use Modules\Hr\Enums\RecruitmentApplicationStatus;
use Modules\Hr\Enums\RecruitmentInterviewLocationType;
use Modules\Hr\Enums\RecruitmentInterviewType;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Livewire\Concerns\Recruitment\ManagesInterviewForm;
use Modules\Hr\Livewire\Concerns\Recruitment\ReadsInterviews;
use Modules\Hr\Models\RecruitmentApplication;
use Modules\Hr\Models\RecruitmentInterview;
use Modules\Hr\Models\RecruitmentInterviewParticipant;
use Modules\Hr\Models\RecruitmentInterviewRequiredParticipant;

/*
 * Schedule an interview for an application (/applications/{id}/interviews/create)
 * or edit one (/interviews/{id}/edit). HR only: the page asks for
 * recruitment-applicants.schedule-interviews on the active role, like the
 * old route guard, and the policy; the service authorizes again on save.
 */
new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.interview_form')] class extends Component
{
    use AuthorizesRecruitment;
    use ManagesInterviewForm;
    use ReadsInterviews;

    public function mount(RecruitmentInterviewSchedulingService $scheduling, ?int $applicationId = null, ?int $interviewId = null): void
    {
        $this->requirePermission('recruitment-applicants.schedule-interviews');

        if ($interviewId !== null) {
            $interview = RecruitmentInterview::query()->visibleTo($this->viewer())->findOrFail($interviewId);
            Gate::authorize('manage', $interview);
            abort_unless($interview->isOpen(), 404);
            $this->fillForEdit($interview, $scheduling);

            return;
        }

        abort_if($applicationId === null, 404);
        $application = $this->findApplication($applicationId);
        Gate::authorize('schedule', [RecruitmentInterview::class, $application]);
        $this->fillForCreate($application, $scheduling);
    }

    #[Computed]
    public function application(): RecruitmentApplication
    {
        return $this->findApplication((int) $this->applicationId)->load([
            'applicant:id,full_name',
            'vacancy:id,position_id',
            'vacancy.position:id,title_en,title_ar',
        ]);
    }

    /** @return array<int, string> */
    #[Computed]
    public function staff(): array
    {
        return $this->staffOptions();
    }

    public function save(RecruitmentInterviewSchedulingService $scheduling): void
    {
        // Errors from a previous attempt must not outlive the fix.
        $this->resetErrorBag();

        try {
            if ($this->interviewId !== null) {
                $interview = RecruitmentInterview::query()->visibleTo($this->viewer())->findOrFail($this->interviewId);
                Gate::authorize('manage', $interview);

                $interview = $scheduling->update(
                    $this->viewer(),
                    $interview,
                    (string) $this->expectedStatus,
                    (string) $this->expectedStartsAt,
                    $this->interviewInput(),
                    $this->reason,
                    $this->notifyCandidate,
                );
                session()->flash('recruitment_toast', __('hr_recruitment.applicant_ui.interviews.updated'));
            } else {
                $application = $this->findApplication((int) $this->applicationId);
                Gate::authorize('schedule', [RecruitmentInterview::class, $application]);

                $interview = $scheduling->schedule($this->viewer(), $application, $this->interviewInput(), $this->notifyCandidate);
                session()->flash('recruitment_toast', __('hr_recruitment.applicant_ui.interviews.scheduled'));
            }
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            $this->toast(collect($exception->errors())->flatten()->first(), false);

            return;
        }

        $this->redirectRoute('hr.recruitment.interviews.show', ['interviewId' => $interview->getKey()], navigate: true);
    }

    private function findApplication(int $applicationId): RecruitmentApplication
    {
        $application = RecruitmentApplication::query()->visibleTo($this->viewer())->findOrFail($applicationId);
        Gate::authorize('view', $application);

        return $application;
    }
};
?>

@php
    $t = 'hr_recruitment.applicant_ui.interviews.';
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $application = $this->application;
    $staff = $this->staff;
    $directory = \Modules\Core\Models\User::directory();
    $nameOf = fn ($userId) => $directory[(int) $userId]['name'] ?? '#'.$userId;
    $editing = $interviewId !== null;
    $title = $editing ? __($t.'edit_title') : __($t.'schedule_title');
    $applicantUrl = Route::has('hr.recruitment.applicants.show') ? route('hr.recruitment.applicants.show', ['applicantId' => $application->applicant_id]) : null;
    $backUrl = $editing ? route('hr.recruitment.interviews.show', ['interviewId' => $interviewId]) : ($applicantUrl ?? route('hr.recruitment.interviews.index'));
    $required = array_map('intval', array_values($requiredParticipants));
    $candidates = array_diff_key($staff, array_flip(array_merge(array_map('intval', array_keys($participants)), $required)));
    $timezone = app(\Modules\Hr\Actions\Recruitment\RecruitmentInterviewSchedulingService::class)->timezone();
@endphp

<div class="space-y-6">
    <x-ui.detail-header
        :back="\App\Support\BackUrl::for($backUrl)"
        :breadcrumbs="[['label' => __('hr_recruitment.hub.section')], ['label' => __('hr_recruitment.hub.titles.interviews'), 'url' => route('hr.recruitment.interviews.index')]]"
        :title="$title"
    >
        <x-slot:meta>
            <span class="font-medium" dir="auto">{{ $application->applicant?->full_name }}</span>
            <span dir="auto">{{ $application->vacancy?->position?->title ?? __('hr_recruitment.applicant_ui.no_vacancy') }}</span>
            <span>{{ __('hr_recruitment.applicant_ui.statuses.'.$application->status) }}</span>
        </x-slot:meta>
    </x-ui.detail-header>

    @error('interview')
        <p role="alert" class="rounded-md border border-status-critical/40 bg-white p-3 text-sm text-status-critical dark:bg-surface-1">{{ $message }}</p>
    @enderror

    <form wire:submit="save" class="space-y-6">
        <x-ui.panel :title="__($t.'details')" :clip="false">
            <div class="grid gap-5 p-4 md:grid-cols-3">
                <x-ui.form-select wire:model="type" :label="__($t.'type')" :help="__($t.'type_hint')" required>
                    @foreach (RecruitmentInterviewType::values() as $value)
                        <option value="{{ $value }}">{{ __($t.'types.'.$value) }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-input wire:model="startsAt" type="datetime-local" :label="__($t.'starts_at')" :help="__($t.'times_in', ['timezone' => $timezone])" required />
                <x-ui.form-select wire:model="duration" :label="__($t.'duration')" required>
                    @foreach ([15, 30, 45, 60, 90, 120, 180, 240] as $minutes)
                        <option value="{{ $minutes }}">{{ __($t.'minutes', ['count' => $minutes]) }}</option>
                    @endforeach
                </x-ui.form-select>

                <x-ui.form-select wire:model.live="locationType" :label="__($t.'location')" required>
                    @foreach (RecruitmentInterviewLocationType::values() as $value)
                        <option value="{{ $value }}">{{ __($t.'location_types.'.$value) }}</option>
                    @endforeach
                </x-ui.form-select>
                @if ($locationType === RecruitmentInterviewLocationType::Online)
                    <x-ui.form-input wire:model="meetingUrl" type="url" placeholder="https://" dir="ltr" :label="__($t.'meeting_url')" :help="__($t.'meeting_url_hint')" required />
                    <x-ui.form-input wire:model="locationValue" :label="__($t.'platform')" :placeholder="__($t.'platform_placeholder')" dir="auto" />
                @else
                    <x-ui.form-input class="md:col-span-2" wire:model="locationValue" :label="__($t.'address')" required dir="auto" />
                @endif
            </div>
        </x-ui.panel>

        <x-ui.panel :title="__($t.'participants')" :subtitle="__($t.'participants_hint')" :clip="false">
            <div class="space-y-5 p-4">
                <div class="rounded-md border border-panel-border p-4">
                    <h3 class="text-sm font-semibold">{{ __($t.'required_panel') }}</h3>
                    <p class="mt-1 text-xs {{ $muted }}">{{ __($t.'required_panel_hint') }}</p>

                    @if (! isset($requiredParticipantOwners[RecruitmentInterviewRequiredParticipant::SEAT_MANAGER]))
                        <p role="alert" class="mt-3 rounded-md border border-status-critical/40 p-3 text-sm text-status-critical">{{ __($t.'errors.required_manager_missing') }}</p>
                    @endif

                    <div class="mt-3 grid gap-4 md:grid-cols-2">
                        @foreach (RecruitmentInterviewRequiredParticipant::seats() as $seat)
                            @continue(! isset($requiredParticipantOwners[$seat]))
                            @php
                                $owner = (int) $requiredParticipantOwners[$seat];
                                $attendee = (int) ($requiredParticipants[$seat] ?? 0);
                            @endphp
                            <div wire:key="required-seat-{{ $seat }}">
                                <x-ui.form-select wire:model="requiredParticipants.{{ $seat }}" :label="__($t.'required_seats.'.$seat)" searchable required
                                    :help="__($t.'seat_owner', ['name' => $nameOf($owner)]).($attendee !== 0 && $attendee !== $owner ? ' · '.__($t.'attending_on_behalf', ['name' => $nameOf($attendee)]) : '')">
                                    @if (! isset($staff[$attendee]) && $attendee !== 0)
                                        <option value="{{ $attendee }}">{{ $nameOf($attendee) }} ({{ __($t.'inactive') }})</option>
                                    @endif
                                    @foreach ($staff as $userId => $label)
                                        <option value="{{ $userId }}">{{ $label }}@if ($userId === $owner) ({{ __($t.'attends_self') }})@endif</option>
                                    @endforeach
                                </x-ui.form-select>
                            </div>
                        @endforeach
                    </div>
                    @error('requiredParticipants')<p class="mt-2 text-sm text-status-critical">{{ $message }}</p>@enderror
                </div>

                <div class="grid items-end gap-3 md:grid-cols-[1fr_12rem_auto]" wire:key="participant-picker">
                    <x-ui.form-select wire:model="newParticipantId" :label="__($t.'add_participant')" searchable>
                        <option value="">{{ __('hr_recruitment.choose') }}</option>
                        @foreach ($candidates as $userId => $label)
                            <option value="{{ $userId }}">{{ $label }}</option>
                        @endforeach
                    </x-ui.form-select>
                    <x-ui.form-select wire:model="newParticipantRole" :label="__($t.'participant_role')">
                        @foreach (RecruitmentInterviewParticipant::roles() as $role)
                            <option value="{{ $role }}">{{ __($t.'roles.'.$role) }}</option>
                        @endforeach
                    </x-ui.form-select>
                    <x-ui.button tone="secondary" icon="plus" wire:click="addParticipant">{{ __($t.'add') }}</x-ui.button>
                </div>
                @error('participants')<p class="text-sm text-status-critical">{{ $message }}</p>@enderror

                @if ($participants === [])
                    <p class="text-sm {{ $muted }}">{{ __($t.'no_participants') }}</p>
                @else
                    <ul class="divide-y divide-panel-border rounded-md border border-panel-border text-sm">
                        @foreach ($participants as $userId => $role)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-3 py-2" wire:key="participant-{{ $userId }}">
                                <div>
                                    <span class="font-medium" dir="auto">{{ $nameOf($userId) }}</span>
                                    <span class="{{ $muted }}">· {{ __($t.'roles.'.$role) }}</span>
                                    @unless (isset($staff[(int) $userId]))
                                        <x-ui.enum-badge class="ms-2" case="inactive" :label="__($t.'inactive')" :tone="\App\Enums\StatusTone::Warn" />
                                    @endunless
                                </div>
                                <x-ui.button tone="ghost" size="sm" icon="x-mark" wire:click="removeParticipant({{ (int) $userId }})">{{ __($t.'remove') }}</x-ui.button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </x-ui.panel>

        <x-ui.panel>
            <div class="space-y-4 p-4">
                @if ($editing)
                    <x-ui.form-textarea wire:model="reason" :label="__($t.'reschedule_reason')" :help="__($t.'reschedule_reason_hint', ['min' => RecruitmentInterviewSchedulingService::MIN_REASON])" rows="3" dir="auto" />
                @endif
                <flux:checkbox wire:model="notifyCandidate" :label="$editing ? __($t.'notify_candidate_change') : __($t.'notify_candidate')" :description="__($t.'notify_candidate_hint')" />
                <div class="flex justify-end gap-2">
                    <x-ui.button tone="ghost" :href="$backUrl" wire:navigate>{{ __('hr_recruitment.hub.cancel') }}</x-ui.button>
                    <x-ui.button type="submit" tone="primary" wire:loading.attr="disabled" wire:target="save">{{ $editing ? __($t.'save_changes') : __($t.'schedule') }}</x-ui.button>
                </div>
            </div>
        </x-ui.panel>
    </form>
</div>
