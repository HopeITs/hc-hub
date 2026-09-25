@php
    use App\Enums\RecruitmentInterviewLocationType;
    use App\Enums\RecruitmentInterviewStatus;
    use App\Models\RecruitmentInterviewParticipant;
    use App\Services\RecruitmentInterviewSchedulingService;
    use Illuminate\Support\Facades\Lang;

    $timezone = $interview->timezone ?: config('app.timezone');
    $starts = $interview->scheduled_at->copy()->setTimezone($timezone);
    $ends = $interview->ends_at?->copy()->setTimezone($timezone);
    $application = $interview->application;
    $personName = fn ($user) => $user?->profile?->name ?? $user?->email ?? '—';
@endphp

<div class="space-y-6 p-6">
    @if (session('recruitment_saved'))
        <p role="status" class="rounded-control border border-green-200 bg-green-50 p-4 text-green-800">{{ session('recruitment_saved') }}</p>
    @endif

    <a href="{{ route('recruitment.interviews.index') }}" wire:navigate class="text-sm font-medium text-primary hover:underline">&larr; {{ __('recruitment-applicant.interviews.all') }}</a>

    @error('interview')
        <p role="alert" class="rounded-control border border-primary bg-primary/5 p-3 text-sm text-primary">{{ $message }}</p>
    @enderror

    {{-- Summary --}}
    <section class="rounded-surface border border-neutral-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-h3 font-semibold">
                    {{ __('recruitment-applicant.interviews.types.'.$interview->interview_type) }}
                    &middot; {{ $application?->applicant?->full_name }}
                </h1>
                <p class="mt-1 text-sm text-secondary-medium">
                    {{ $application?->campaign?->vacancy?->position?->title ?? __('recruitment-applicant.no_vacancy') }}
                    @if ($canSeeApplicant)
                        &middot; <a href="{{ route('recruitment.applicants.show', ['applicantId' => $application->applicant_id]) }}" wire:navigate class="text-primary hover:underline">{{ __('recruitment-applicant.interviews.open_applicant') }}</a>
                    @endif
                </p>
            </div>
            <span class="rounded-control px-3 py-1 text-sm font-semibold {{ RecruitmentInterviewStatus::classes($interview->status) }}">
                {{ __('recruitment-applicant.interviews.statuses.'.$interview->status) }}
            </span>
        </div>

        <dl class="mt-6 grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
            <div>
                <dt class="text-secondary-medium">{{ __('recruitment-applicant.interviews.when') }}</dt>
                <dd class="mt-1 font-medium">
                    {{ $starts->translatedFormat('l j F Y') }} &middot; {{ $starts->format('H:i') }}@if ($ends) – {{ $ends->format('H:i') }}@endif
                    <span class="block text-xs font-normal text-secondary-medium">{{ $starts->format('Y-m-d H:i') }} &middot; {{ $timezone }} (UTC{{ $starts->format('P') }})</span>
                </dd>
            </div>
            <div>
                <dt class="text-secondary-medium">{{ __('recruitment-applicant.interviews.location') }}</dt>
                <dd class="mt-1 font-medium">
                    {{ __('recruitment-applicant.interviews.location_types.'.$interview->location_type) }}
                    @if ($interview->location_value)
                        <span class="block font-normal">{{ $interview->location_value }}</span>
                    @endif
                    @if ($interview->location_type === RecruitmentInterviewLocationType::Online)
                        @if ($meetingUrl)
                            <a href="{{ $meetingUrl }}" target="_blank" rel="noopener noreferrer" class="block break-all font-normal text-primary hover:underline">{{ $meetingUrl }}</a>
                        @else
                            <span class="block text-xs font-normal text-secondary-medium">{{ __('recruitment-applicant.interviews.meeting_url_hidden') }}</span>
                        @endif
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-secondary-medium">{{ __('recruitment-applicant.interviews.scheduled_by') }}</dt>
                <dd class="mt-1">{{ $personName($interview->creator) }} &middot; {{ $interview->created_at?->format('Y-m-d H:i') }}</dd>
            </div>
            @if ($interview->reschedule_count > 0)
                <div>
                    <dt class="text-secondary-medium">{{ __('recruitment-applicant.interviews.reschedule_count') }}</dt>
                    <dd class="mt-1">{{ $interview->reschedule_count }}</dd>
                </div>
            @endif
        </dl>

        @if ($interview->status === RecruitmentInterviewStatus::Cancelled)
            @php $reasonKey = 'recruitment-applicant.interviews.cancellation_reasons.'.$interview->cancellation_reason; @endphp
            <div class="mt-6 rounded-control border border-neutral-200 bg-neutral-50 p-4 text-sm">
                <p class="font-medium">
                    {{ __('recruitment-applicant.interviews.cancelled_on', ['date' => $interview->cancelled_at?->format('Y-m-d H:i'), 'by' => $interview->cancelledBy ? $personName($interview->cancelledBy) : __('recruitment-applicant.interviews.system')]) }}
                </p>
                <p class="mt-1">{{ __('recruitment-applicant.interviews.cancel_reason') }}: {{ Lang::has($reasonKey) ? __($reasonKey) : $interview->cancellation_reason }}</p>
                @if ($canManage && $interview->cancellation_note)
                    <p class="mt-1 whitespace-pre-line text-secondary-medium">{{ $interview->cancellation_note }}</p>
                @endif
            </div>
        @elseif (in_array($interview->status, [RecruitmentInterviewStatus::Completed, RecruitmentInterviewStatus::NoShow], true))
            <div class="mt-6 rounded-control border border-neutral-200 bg-neutral-50 p-4 text-sm">
                <p class="font-medium">
                    {{ __('recruitment-applicant.interviews.outcome_on', ['date' => $interview->outcome_recorded_at?->format('Y-m-d H:i'), 'by' => $personName($interview->outcomeRecordedBy)]) }}
                </p>
                @if ($canManage && $interview->outcome_note)
                    <p class="mt-1 whitespace-pre-line text-secondary-medium">{{ $interview->outcome_note }}</p>
                @endif
            </div>
        @endif

        {{-- HR actions --}}
        @if ($canManage && $interview->isOpen())
            <div class="mt-6 flex flex-wrap gap-2 border-t border-neutral-100 pt-4">
                <a href="{{ route('recruitment.interviews.edit', ['interviewId' => $interview->id]) }}" wire:navigate
                   class="rounded-control border border-primary px-3 py-1.5 text-sm font-semibold text-primary hover:bg-primary/5">
                    {{ __('recruitment-applicant.interviews.actions.edit') }}
                </a>
                @if ($hasStarted)
                    <button type="button" wire:click="beginOutcome('{{ RecruitmentInterviewStatus::Completed }}')" class="rounded-control border border-neutral-300 px-3 py-1.5 text-sm hover:bg-neutral-50">
                        {{ __('recruitment-applicant.interviews.actions.completed') }}
                    </button>
                    <button type="button" wire:click="beginOutcome('{{ RecruitmentInterviewStatus::NoShow }}')" class="rounded-control border border-neutral-300 px-3 py-1.5 text-sm hover:bg-neutral-50">
                        {{ __('recruitment-applicant.interviews.actions.no_show') }}
                    </button>
                @endif
                <button type="button" wire:click="beginCancel" class="rounded-control border border-neutral-300 px-3 py-1.5 text-sm text-primary hover:bg-primary/5">
                    {{ __('recruitment-applicant.interviews.actions.cancel') }}
                </button>
            </div>
            @unless ($hasStarted)
                <p class="mt-2 text-xs text-secondary-medium">{{ __('recruitment-applicant.interviews.outcome_after_start') }}</p>
            @endunless
        @endif

        @if ($panel === 'cancel')
            <div class="mt-4 space-y-3 rounded-control border border-primary/40 bg-primary/5 p-4">
                <h3 class="font-semibold">{{ __('recruitment-applicant.interviews.cancel_title') }}</h3>
                <p class="text-sm text-secondary-medium">{{ __('recruitment-applicant.interviews.cancel_hint') }}</p>
                <div>
                    <x-forms.input-label for="cancelReason" required :value="__('recruitment-applicant.interviews.cancel_reason')" />
                    <select id="cancelReason" wire:model.live="cancelReason" class="w-full rounded-control border border-neutral-300 bg-white px-3 py-2 text-body">
                        <option value="">{{ __('recruitment.choose') }}</option>
                        @foreach ($cancellationReasons as $code)
                            <option value="{{ $code }}">{{ __('recruitment-applicant.interviews.cancellation_reasons.'.$code) }}</option>
                        @endforeach
                    </select>
                    <x-forms.input-error field="cancelReason" input-id="cancelReason" />
                </div>
                <div>
                    <x-forms.input-label for="cancelNote" :required="$cancelReason === RecruitmentInterviewSchedulingService::REASON_OTHER" :value="__('recruitment-applicant.interviews.internal_note')" />
                    <x-forms.textarea id="cancelNote" wire:model="cancelNote" rows="2" />
                    <p class="mt-1 text-xs text-secondary-medium">{{ __('recruitment-applicant.interviews.internal_note_hint') }}</p>
                    <x-forms.input-error field="cancelNote" input-id="cancelNote" />
                </div>
                @unless ($hasStarted)
                    <label class="flex items-start gap-2 text-sm">
                        <input type="checkbox" wire:model="notifyCandidate" class="mt-0.5 size-4 rounded-inset border-neutral-300 text-primary focus:ring-primary">
                        <span>{{ __('recruitment-applicant.interviews.notify_candidate_cancel') }}
                            <span class="block text-xs text-secondary-medium">{{ __('recruitment-applicant.interviews.notify_candidate_hint') }}</span>
                        </span>
                    </label>
                @endunless
                <div class="flex gap-2">
                    <x-primary-button type="button" wire:click="cancelInterview" wire:loading.attr="disabled">{{ __('recruitment-applicant.interviews.confirm_cancel') }}</x-primary-button>
                    <button type="button" wire:click="closePanel" class="px-3 py-1.5 text-sm text-secondary-dark hover:underline">{{ __('recruitment-applicant.cancel') }}</button>
                </div>
            </div>
        @elseif (in_array($panel, [RecruitmentInterviewStatus::Completed, RecruitmentInterviewStatus::NoShow], true))
            <div class="mt-4 space-y-3 rounded-control border border-neutral-300 bg-neutral-50 p-4">
                <h3 class="font-semibold">{{ __('recruitment-applicant.interviews.outcome_title.'.$panel) }}</h3>
                <div>
                    <x-forms.input-label for="outcomeNote" :value="__('recruitment-applicant.interviews.internal_note')" />
                    <x-forms.textarea id="outcomeNote" wire:model="outcomeNote" rows="2" />
                    <p class="mt-1 text-xs text-secondary-medium">{{ __('recruitment-applicant.interviews.outcome_note_hint') }}</p>
                    <x-forms.input-error field="outcome" input-id="outcomeNote" />
                </div>
                <div class="flex gap-2">
                    <x-primary-button type="button" wire:click="recordOutcome" wire:loading.attr="disabled">{{ __('recruitment-applicant.interviews.confirm_outcome') }}</x-primary-button>
                    <button type="button" wire:click="closePanel" class="px-3 py-1.5 text-sm text-secondary-dark hover:underline">{{ __('recruitment-applicant.cancel') }}</button>
                </div>
            </div>
        @endif
    </section>

    {{-- Participants --}}
    <section class="rounded-surface border border-neutral-200 bg-white p-6">
        <h2 class="text-h4 font-semibold">{{ __('recruitment-applicant.interviews.participants') }}</h2>
        @if ($interview->participants->isEmpty())
            <p class="mt-2 text-sm text-secondary-medium">{{ __('recruitment-applicant.interviews.no_participants') }}</p>
        @else
            <ul class="mt-3 divide-y divide-neutral-100 text-sm">
                @foreach ($interview->participants as $participant)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2 {{ $participant->removed_at ? 'text-secondary-soft' : '' }}" wire:key="participant-{{ $participant->id }}">
                        <div>
                            <span class="font-medium {{ $participant->removed_at ? 'line-through' : '' }}">{{ $personName($participant->user) }}</span>
                            <span class="text-secondary-medium">&middot; {{ __('recruitment-applicant.interviews.roles.'.$participant->role) }}</span>
                            @unless (RecruitmentInterviewSchedulingService::isActiveUser($participant->user))
                                <span class="ms-2 rounded-control bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">{{ __('recruitment-applicant.interviews.inactive') }}</span>
                            @endunless
                            @if ($participant->removed_at)
                                <span class="ms-2 text-xs">{{ __('recruitment-applicant.interviews.removed_on', ['date' => $participant->removed_at->format('Y-m-d H:i')]) }}</span>
                            @endif
                        </div>
                        @if ($canManage)
                            <span class="text-xs text-secondary-medium">{{ __('recruitment-applicant.interviews.notification_statuses.'.$participant->notification_status) }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Task 8: the viewer's own evaluation (renders nothing unless they may evaluate) --}}
    <livewire:recruitment.interviews.evaluation-form :interview-id="$interview->id" :key="'evaluation-form-'.$interview->id.'-'.$interview->status" />

    {{-- Task 8: every reviewer's evaluation — HR with view-evaluations in scope only --}}
    @can('viewEvaluations', $interview)
        <livewire:recruitment.interviews.evaluation-summary :interview-id="$interview->id" :key="'evaluation-summary-'.$interview->id.'-'.$interview->status" />
    @endcan

    {{-- Reschedule history --}}
    @if ($interview->reschedules->isNotEmpty())
        <section class="rounded-surface border border-neutral-200 bg-white p-6">
            <h2 class="text-h4 font-semibold">{{ __('recruitment-applicant.interviews.history') }}</h2>
            <ol class="mt-3 space-y-3 text-sm">
                @foreach ($interview->reschedules as $change)
                    <li class="border-s-2 border-neutral-200 ps-3" wire:key="reschedule-{{ $change->id }}">
                        <p class="font-medium">
                            {{ $change->from_scheduled_at->copy()->setTimezone($timezone)->format('Y-m-d H:i') }}
                            &rarr; {{ $change->to_scheduled_at->copy()->setTimezone($timezone)->format('Y-m-d H:i') }}
                            @if ($change->location_changed)
                                <span class="ms-1 rounded-control bg-neutral-100 px-2 py-0.5 text-xs font-normal">{{ __('recruitment-applicant.interviews.location_changed') }}</span>
                            @endif
                            @if ($change->reminder_already_sent)
                                <span class="ms-1 rounded-control bg-amber-50 px-2 py-0.5 text-xs font-normal text-amber-700">{{ __('recruitment-applicant.interviews.after_reminder') }}</span>
                            @endif
                        </p>
                        <p class="text-secondary-medium">{{ $personName($change->rescheduledBy) }} &middot; {{ $change->rescheduled_at->format('Y-m-d H:i') }}</p>
                        <p class="mt-1 whitespace-pre-line">{{ $change->reason }}</p>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    {{-- Candidate emails: HR only --}}
    @if ($canManage)
        <section class="rounded-surface border border-neutral-200 bg-white p-6">
            <h2 class="text-h4 font-semibold">{{ __('recruitment-applicant.interviews.candidate_emails') }}</h2>
            @if ($notices->isEmpty())
                <p class="mt-2 text-sm text-secondary-medium">{{ __('recruitment-applicant.interviews.no_candidate_emails') }}</p>
            @else
                <ul class="mt-3 divide-y divide-neutral-100 text-sm">
                    @foreach ($notices as $notice)
                        <li class="flex flex-wrap justify-between gap-2 py-2" wire:key="notice-{{ $notice->id }}">
                            <span>
                                {{ __('recruitment-applicant.interviews.notice_kinds.'.$notice->kind) }}
                                <span class="text-secondary-medium">&middot; {{ __('recruitment-applicant.interviews.for_slot', ['slot' => $notice->scheduled_for->copy()->setTimezone($timezone)->format('Y-m-d H:i')]) }}</span>
                            </span>
                            <span class="text-secondary-medium">
                                {{ __('recruitment-applicant.interviews.notice_statuses.'.$notice->status) }}
                                &middot; {{ ($notice->sent_at ?? $notice->failed_at ?? $notice->queued_at)?->format('Y-m-d H:i') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif
</div>
