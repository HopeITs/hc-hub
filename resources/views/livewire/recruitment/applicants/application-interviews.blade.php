@php use App\Enums\RecruitmentInterviewStatus; @endphp
<div class="mt-4 rounded-control border border-neutral-200 p-3 text-sm">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h4 class="font-semibold">{{ __('recruitment-applicant.interviews.title') }} ({{ $interviews->count() }})</h4>

        @if ($canSchedule)
            <a href="{{ route('recruitment.interviews.create', ['applicationId' => $application->id]) }}" wire:navigate
               class="rounded-control border border-primary px-3 py-1 text-sm font-semibold text-primary hover:bg-primary/5">
                {{ __('recruitment-applicant.interviews.schedule') }}
            </a>
        @endif
    </div>

    @if ($mayScheduleAtAll && ! $canSchedule)
        <p class="mt-2 text-xs text-secondary-medium">
            {{ $isRejected ? __('recruitment-applicant.interviews.errors.application_rejected') : __('recruitment-applicant.interviews.errors.not_shortlisted') }}
        </p>
    @endif

    @if ($interviews->isEmpty())
        <p class="mt-2 text-secondary-medium">{{ __('recruitment-applicant.interviews.none_yet') }}</p>
    @else
        <ul class="mt-2 divide-y divide-neutral-100">
            @foreach ($interviews as $interview)
                @php $timezone = $interview->timezone ?: config('app.timezone'); @endphp
                <li class="flex flex-wrap items-center justify-between gap-2 py-2" wire:key="application-interview-{{ $interview->id }}">
                    <a href="{{ route('recruitment.interviews.show', ['interviewId' => $interview->id]) }}" wire:navigate class="hover:underline">
                        <span class="font-medium">{{ __('recruitment-applicant.interviews.types.'.$interview->interview_type) }}</span>
                        <span class="text-secondary-medium">
                            &middot; {{ $interview->scheduled_at->copy()->setTimezone($timezone)->format('Y-m-d H:i') }}
                            &middot; {{ __('recruitment-applicant.interviews.location_types.'.$interview->location_type) }}
                            &middot; {{ trans_choice('recruitment-applicant.interviews.participant_count', $interview->participants_count, ['count' => $interview->participants_count]) }}
                        </span>
                    </a>
                    <span class="rounded-control px-2 py-0.5 text-xs font-semibold {{ RecruitmentInterviewStatus::classes($interview->status) }}">
                        {{ __('recruitment-applicant.interviews.statuses.'.$interview->status) }}
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
