{{--
    The week, day and list views of ⚡team-calendar: one block per day with
    its holidays, birthdays, counts and every absence in full. The list view
    leaves out quiet days; the week and day views show every day.
--}}
@php
    $listDays = collect($calendar['days'])->filter(fn (array $day): bool => $viewName !== 'agenda'
        || $day['members'] !== []
        || ($calendar['holidays'][$day['date']] ?? []) !== []
        || ($calendar['birthdays'][$day['date']] ?? []) !== []);
@endphp

@if ($listDays->isEmpty())
    <x-ui.empty-state icon="calendar-days" :heading="__('hr_leave.calendar.empty')" />
@else
    <x-ui.panel as="ol" class="divide-y divide-secondary-soft dark:divide-line" data-calendar-list>
        @foreach ($listDays as $day)
            @php
                $date = $day['date'];
                $dayDate = \Carbon\CarbonImmutable::parse($date);
                $dayHolidays = $calendar['holidays'][$date] ?? [];
                $dayBirthdays = $calendar['birthdays'][$date] ?? [];
            @endphp
            <li wire:key="cal-day-{{ $date }}" @class(['flex flex-col gap-2 px-4 py-3 sm:flex-row sm:gap-4', 'bg-secondary-soft/40 dark:bg-surface-2/40' => $day['is_weekend'] || $day['is_holiday'] || $dayHolidays !== []]) data-calendar-day="{{ $date }}">
                <div class="w-36 shrink-0">
                    <p @class(['text-sm font-semibold', 'text-primary dark:text-brand-text' => $date === $today])>{{ $dayDate->translatedFormat('l j M') }}</p>
                    @if ($day['is_weekend'])
                        <p class="text-xs text-secondary-medium dark:text-ink-muted">{{ __('hr_leave.calendar.weekend') }}</p>
                    @endif
                    @if ($calendar['showCounts'] && $day['expected'] > 0)
                        <p class="text-xs tabular-nums text-secondary-medium dark:text-ink-muted">{{ __('hr_leave.calendar.away_of', ['away' => $day['away'], 'expected' => $day['expected']]) }}</p>
                    @endif
                    @if ($day['pending'] > 0)
                        <p class="text-xs tabular-nums text-status-warn">{{ __('hr_leave.calendar.pending_count', ['count' => $day['pending']]) }}</p>
                    @endif
                    @if ($calendar['canManageHolidays'])
                        <button type="button" class="mt-1 text-xs font-semibold text-secondary-medium underline underline-offset-2 hover:text-secondary-dark dark:text-ink-muted dark:hover:text-ink" wire:click="openCreateHoliday('{{ $date }}')">{{ __('hr_leave.calendar.add_holiday') }}</button>
                    @endif
                </div>

                <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                    @foreach ($dayHolidays as $holiday)
                        <p class="flex items-center gap-1.5 text-sm font-semibold text-status-critical">
                            <flux:icon.sun variant="micro" class="size-3.5 shrink-0" aria-hidden="true" />
                            @if ($holiday['editable'])
                                <button type="button" class="truncate hover:underline" wire:click="openEditHoliday({{ $holiday['id'] }})" aria-label="{{ __('hr_leave.calendar.edit_holiday', ['name' => $holiday['name']]) }}">{{ $holiday['name'] }}</button>
                            @else
                                <span class="truncate">{{ $holiday['name'] }}</span>
                            @endif
                        </p>
                    @endforeach

                    @foreach ($dayBirthdays as $birthday)
                        <p class="flex items-center gap-1.5 text-sm text-secondary-medium dark:text-ink-muted" wire:key="{{ $birthday['key'] }}">
                            <flux:icon.cake variant="micro" class="size-3.5 shrink-0" aria-hidden="true" />
                            <span dir="auto">{{ $birthday['is_self'] ? __('hr_leave.calendar.your_birthday') : __('hr_leave.calendar.birthday_of', ['name' => $birthday['name']]) }}</span>
                        </p>
                    @endforeach

                    @forelse ($day['members'] as $entry)
                        <x-hr::leave.calendar-entry :entry="$entry" wire:key="cal-entry-{{ $date }}-{{ $entry['key'] }}" />
                    @empty
                        @if ($dayHolidays === [] && $dayBirthdays === [])
                            <p class="text-sm text-secondary-medium dark:text-ink-muted">{{ __('hr_leave.calendar.nobody') }}</p>
                        @endif
                    @endforelse
                </div>
            </li>
        @endforeach
    </x-ui.panel>
@endif
