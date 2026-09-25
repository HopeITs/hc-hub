@php use App\Enums\RecruitmentInterviewStatus; @endphp
<section class="rounded-surface border border-neutral-200 bg-white p-4 sm:p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <button type="button" wire:click="previousMonth" class="rounded-control border border-neutral-200 px-2.5 py-1 text-sm hover:bg-neutral-50" aria-label="{{ __('recruitment-applicant.interviews.previous_month') }}">
                <span class="rtl:hidden">&lsaquo;</span><span class="ltr:hidden">&rsaquo;</span>
            </button>
            <h2 class="min-w-40 text-center text-h4 font-semibold">{{ $first->translatedFormat('F Y') }}</h2>
            <button type="button" wire:click="nextMonth" class="rounded-control border border-neutral-200 px-2.5 py-1 text-sm hover:bg-neutral-50" aria-label="{{ __('recruitment-applicant.interviews.next_month') }}">
                <span class="rtl:hidden">&rsaquo;</span><span class="ltr:hidden">&lsaquo;</span>
            </button>
            <button type="button" wire:click="thisMonth" class="ms-2 text-sm text-primary hover:underline">{{ __('recruitment-applicant.interviews.today') }}</button>
        </div>

        <div class="flex items-center gap-4 text-sm text-secondary-medium">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" wire:model.live="showCancelled" class="size-4 rounded-inset border-neutral-300 text-primary focus:ring-primary">
                {{ __('recruitment-applicant.interviews.show_cancelled') }}
            </label>
            <span>{{ __('recruitment-applicant.interviews.times_in', ['timezone' => $timezone]) }}</span>
        </div>
    </div>

    <div class="mt-4 overflow-x-auto">
        <div class="min-w-[720px]">
            <div class="grid grid-cols-7 border-b border-neutral-200 pb-2 text-center text-xs font-semibold text-secondary-medium">
                @foreach ($weekdays as $weekday)
                    <span>{{ $weekday }}</span>
                @endforeach
            </div>

            @foreach ($weeks as $week)
                <div class="grid grid-cols-7 border-b border-neutral-100" wire:key="week-{{ $week[0]['date']->toDateString() }}">
                    @foreach ($week as $day)
                        <div class="min-h-28 border-e border-neutral-100 p-1.5 last:border-e-0 {{ $day['inMonth'] ? '' : 'bg-neutral-50' }}">
                            <div class="mb-1 flex justify-end">
                                <span class="flex size-6 items-center justify-center rounded-full text-xs
                                    {{ $day['isToday'] ? 'bg-primary font-semibold text-white' : ($day['inMonth'] ? 'text-secondary-dark' : 'text-secondary-soft') }}">
                                    {{ $day['date']->day }}
                                </span>
                            </div>

                            <ul class="space-y-1">
                                @foreach ($day['interviews'] as $interview)
                                    <li wire:key="calendar-interview-{{ $interview->id }}">
                                        <a href="{{ route('recruitment.interviews.show', ['interviewId' => $interview->id]) }}" wire:navigate
                                           class="block truncate rounded px-1.5 py-1 text-xs {{ RecruitmentInterviewStatus::classes($interview->status) }} {{ $interview->status === RecruitmentInterviewStatus::Cancelled ? 'line-through' : '' }}"
                                           title="{{ $interview->application?->applicant?->full_name }} — {{ __('recruitment-applicant.interviews.types.'.$interview->interview_type) }} — {{ __('recruitment-applicant.interviews.statuses.'.$interview->status) }}">
                                            <span class="font-semibold">{{ $interview->scheduled_at->format('H:i') }}</span>
                                            {{ $interview->application?->applicant?->full_name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</section>
