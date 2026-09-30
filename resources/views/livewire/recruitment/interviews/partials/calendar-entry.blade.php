{{-- One interview in the calendar. Type and status are in the link text itself, not a hover-only title. --}}
@php
    use App\Enums\RecruitmentInterviewStatus;

    $cancelled = $interview->status === RecruitmentInterviewStatus::Cancelled;
    $status = __('recruitment-applicant.interviews.statuses.'.$interview->status);
    $type = __('recruitment-applicant.interviews.types.'.$interview->interview_type);
@endphp
<li wire:key="calendar-interview-{{ $interview->id }}">
    <a href="{{ route('recruitment.interviews.show', ['interviewId' => $interview->id]) }}" wire:navigate
       class="block rounded-inset transition hover:brightness-95 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary
              {{ RecruitmentInterviewStatus::classes($interview->status) }}
              {{ $compact ? 'truncate px-1.5 py-1 text-xs' : 'px-3 py-2 text-sm' }}">
        <span class="font-semibold tabular-nums">{{ $interview->scheduled_at->format('H:i') }}</span>
        <span class="{{ $cancelled ? 'line-through' : '' }}">{{ $interview->application?->applicant?->full_name }}</span>
        @if ($compact)
            <span class="sr-only">&middot; {{ $type }} &middot; {{ $status }}</span>
        @else
            <span class="block text-xs opacity-80">{{ $type }} &middot; {{ $status }}</span>
        @endif
    </a>
</li>
