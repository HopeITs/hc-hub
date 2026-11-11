{{--
    The month view of ⚡team-calendar: six Sunday-first week rows; each
    absence is one bar across its days within a week (up to three lanes,
    then "+N more"), with holidays, birthdays and counts on each day.
--}}
@php
    $weeks = $this::monthWeeks($calendar['days']);
    $monthNumber = $anchor->month;
    $weekdayNames = collect(range(0, 6))->map(fn (int $offset): string => $from->addDays($offset)->translatedFormat('D'));
@endphp

<div class="overflow-x-auto rounded-xl border border-board-line bg-board shadow-card dark:border-transparent dark:shadow-raised" data-calendar-month>
    <div class="min-w-[44rem]">
        <div class="grid grid-cols-7 border-b border-board-line bg-board-head">
            @foreach ($weekdayNames as $weekdayName)
                <span class="px-2 py-2 type-label text-secondary-medium dark:text-ink-faint">{{ $weekdayName }}</span>
            @endforeach
        </div>

        @foreach ($weeks as $weekIndex => $week)
            <div class="grid grid-cols-7 border-b border-board-line last:border-b-0" wire:key="cal-week-{{ $weekIndex }}" style="grid-template-rows: auto repeat({{ max($week['lanes'], 1) }}, minmax(1.5rem, auto)) minmax(0.5rem, auto)">
                @foreach ($week['days'] as $col => $day)
                    @php
                        $date = $day['date'];
                        $dayHolidays = $calendar['holidays'][$date] ?? [];
                        $dayBirthdays = $calendar['birthdays'][$date] ?? [];
                        $isOtherMonth = (int) substr($date, 5, 2) !== $monthNumber;
                    @endphp
                    <div
                        @class([
                            'border-e border-board-line',
                            'border-e-0' => $col === 6,
                            'bg-secondary-soft/40 dark:bg-surface-2/40' => $day['is_weekend'] || $dayHolidays !== [] || $day['is_holiday'],
                        ])
                        style="grid-column: {{ $col + 1 }}; grid-row: 1 / -1"
                        aria-hidden="true"
                    ></div>
                    <div
                        @class(['flex min-w-0 flex-col gap-0.5 px-1.5 pt-1.5', 'opacity-60' => $isOtherMonth])
                        style="grid-column: {{ $col + 1 }}; grid-row: 1"
                        data-calendar-day="{{ $date }}"
                    >
                        <div class="flex items-center justify-between gap-1">
                            <button type="button" wire:click="openDay('{{ $date }}')" @class([
                                'inline-flex size-6 items-center justify-center rounded-full text-xs font-semibold tabular-nums',
                                'bg-primary text-white dark:bg-brand' => $date === $today,
                                'text-secondary-dark hover:bg-secondary-soft dark:text-ink dark:hover:bg-surface-2' => $date !== $today,
                            ]) aria-label="{{ __('hr_leave_calendar.open_day', ['date' => \Carbon\CarbonImmutable::parse($date)->translatedFormat('j F')]) }}">{{ (int) substr($date, 8, 2) }}</button>

                            @if ($calendar['showCounts'] && $day['expected'] > 0 && ($day['away'] > 0 || $day['pending'] > 0))
                                <span class="truncate text-[10px] tabular-nums text-secondary-medium dark:text-ink-muted">{{ __('hr_leave_calendar.away_of', ['away' => $day['away'], 'expected' => $day['expected']]) }}</span>
                            @endif
                        </div>

                        @foreach ($dayHolidays as $holiday)
                            @if ($holiday['editable'])
                                <button type="button" class="truncate rounded px-1 text-start text-[11px] font-semibold text-status-critical hover:underline" wire:click="openEditHoliday({{ $holiday['id'] }})" title="{{ $holiday['name'] }}" aria-label="{{ __('hr_leave_calendar.edit_holiday', ['name' => $holiday['name']]) }}">{{ $holiday['name'] }}</button>
                            @else
                                <span class="truncate px-1 text-[11px] font-semibold text-status-critical" title="{{ $holiday['name'] }}">{{ $holiday['name'] }}</span>
                            @endif
                        @endforeach

                        @foreach ($dayBirthdays as $birthday)
                            <span class="flex items-center gap-1 truncate px-1 text-[11px] text-secondary-medium dark:text-ink-muted" wire:key="{{ $birthday['key'] }}">
                                <flux:icon.cake variant="micro" class="size-3 shrink-0" aria-hidden="true" />
                                <span class="truncate" dir="auto">{{ $birthday['is_self'] ? __('hr_leave_calendar.your_birthday') : __('hr_leave_calendar.birthday_of', ['name' => $birthday['name']]) }}</span>
                            </span>
                        @endforeach
                    </div>
                @endforeach

                @foreach ($week['bars'] as $bar)
                    <div class="min-w-0 px-0.5 py-px" style="grid-column: {{ $bar['col'] }} / span {{ $bar['span'] }}; grid-row: {{ $bar['lane'] + 2 }}" wire:key="bar-{{ $weekIndex }}-{{ $bar['entry']['key'] }}">
                        <x-hr::leave.calendar-entry :entry="$bar['entry']" compact :class="($bar['continues_before'] ? 'rounded-s-none ' : '').($bar['continues_after'] ? 'rounded-e-none' : '')" />
                    </div>
                @endforeach

                @foreach ($week['hidden'] as $col => $hiddenCount)
                    @if ($hiddenCount > 0)
                        <button type="button" class="px-2 pb-1 text-start text-[11px] font-semibold text-secondary-medium hover:underline dark:text-ink-muted" style="grid-column: {{ $col + 1 }}; grid-row: -2" wire:click="openDay('{{ $week['days'][$col]['date'] }}')">{{ __('hr_leave_calendar.more', ['count' => $hiddenCount]) }}</button>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
</div>

@unless ($calendar['hasAbsences'])
    <p class="mt-3 text-sm text-secondary-medium dark:text-ink-muted">{{ __('hr_leave_calendar.empty') }}</p>
@endunless
