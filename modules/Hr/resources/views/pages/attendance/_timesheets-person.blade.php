{{--
    One person's month as a calendar: the days that wait for an answer are red and
    answered with two buttons (worked / absent), then the month is confirmed. Lock,
    the signed paper and the printout stay out of the way. Included by ⚡timesheets.
--}}
@php
    use Carbon\CarbonImmutable;
    use Modules\Hr\Models\Employee;
    use Modules\Hr\Support\Attendance\Timesheet as Sheet;

    $pEmployee = $panel['employee'];
    $pRecord = $panel['record'];
    $pCan = $panel['can'];
    $pSheet = $panel['sheet'];
    $pTotals = $pSheet['totals'];
    // The paper prints only when nothing waits: no day for HR, no leave request unanswered.
    $pBlocked = $pTotals['to_decide'] > 0 || $pTotals['leave_pending'] > 0;
    $pending = [Sheet::ONE_PUNCH, Sheet::NO_RECORD];
    $lead = ($monthDate->startOfMonth()->dayOfWeek + 1) % 7; // weeks start on Saturday
    $planned = collect($pSheet['rows'])->whereNotIn('kind', [Sheet::WEEKEND, Sheet::HOLIDAY, Sheet::OUTSIDE])->count();
    $leaveDays = collect($pSheet['rows'])->where('kind', Sheet::LEAVE)->count();
    $hoursWorked = intdiv((int) collect($pSheet['rows'])->where('kind', Sheet::WORKED)->sum('worked_minutes'), 60);
    $selected = $openDay && $openDay['employee']->id === $pEmployee->id ? $openDay['row'] : null;
    $selectedDate = $selected ? $selected['date']->toDateString() : null;
    $hm = fn ($time): string => $time ? $time->format('H:i') : '';
    $off = $pSheet['placement']['days_off'] ?? [];
    $offFrom = $pSheet['placement']['days_off_from'] ?? null;
    $offNames = collect($off)->map(fn (int $day): string => CarbonImmutable::now()->startOfWeek(0)->addDays($day)->locale(app()->getLocale())->isoFormat('dddd'))->join(app()->getLocale() === 'ar' ? '، ' : ', ');
@endphp

<div class="grid gap-y-5" data-attendance-month-panel="{{ $pEmployee->id }}">
    @unless ($own)
        <div>
            <x-ui.button tone="ghost" size="sm" icon="chevron-left" class="rtl:rotate-180" wire:click="closeMonth" data-test="attendance-back">{{ __('hr_attendance.timesheets.back') }}</x-ui.button>
        </div>
    @endunless

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <x-ui.avatar :src="Employee::photoUrl($pEmployee->id)" class="size-12 rounded-full" :size="48" />
            <div>
                <h2 class="text-xl font-semibold" dir="auto">{{ $pEmployee->name }}</h2>
                <p class="text-sm {{ $muted }}"><span class="tabular-nums">#{{ $pEmployee->id }}</span>@if ($pSheet['placement']['program']) · <bdi>{{ $pSheet['placement']['program'] }}</bdi>@endif</p>
            </div>
        </div>
        @if ($pBlocked)
            <x-ui.button size="sm" icon="printer" disabled title="{{ __('hr_attendance.timesheets.print-blocked') }}" data-test="attendance-print-blocked">{{ __('hr_attendance.timesheets.print') }}</x-ui.button>
        @else
            <x-ui.button size="sm" icon="printer" target="_blank" :href="route('hr.attendance.print', ['month' => $month, 'employees' => [$pEmployee->id]])" data-test="attendance-print-person">{{ __('hr_attendance.timesheets.print') }}</x-ui.button>
        @endif
    </div>

    <dl class="grid grid-cols-3 gap-3">
        <div class="rounded-lg bg-secondary-soft/60 p-3 dark:bg-surface-2">
            <dt class="text-xs {{ $muted }}">{{ __('hr_attendance.timesheets.days-worked') }}</dt>
            <dd class="text-xl font-semibold tabular-nums">{{ $pTotals['working_days'] + 0 }} <span class="text-sm font-normal {{ $muted }}">/ {{ $planned }}</span></dd>
        </div>
        <div class="rounded-lg bg-secondary-soft/60 p-3 dark:bg-surface-2">
            <dt class="text-xs {{ $muted }}">{{ __('hr_attendance.timesheets.hours') }}</dt>
            <dd class="text-xl font-semibold tabular-nums">{{ $hoursWorked }}</dd>
        </div>
        <div class="rounded-lg bg-secondary-soft/60 p-3 dark:bg-surface-2">
            <dt class="text-xs {{ $muted }}">{{ __('hr_attendance.timesheets.leave') }}</dt>
            <dd class="text-xl font-semibold tabular-nums">{{ $leaveDays }}</dd>
        </div>
    </dl>

    {{-- This person's own weekly days off: from the contract, else the center --}}
    @if ($offFrom)
        <p class="flex flex-wrap items-center gap-x-2 text-sm" data-attendance-days-off>
            <flux:icon name="calendar" class="size-4 {{ $muted }}" />
            <span class="font-semibold">{{ __('hr_attendance.timesheets.days-off') }}:</span>
            <span>{{ $offNames ?: '—' }}</span>
            <span class="{{ $muted }}">({{ __('hr_attendance.timesheets.days-off-from.'.$offFrom, ['center' => $pSheet['placement']['center'] ?? '—']) }})</span>
        </p>
    @endif

    @if ($pTotals['to_decide'] > 0)
        <p class="rounded-lg border-2 border-primary px-4 py-3 text-sm font-semibold dark:border-brand" role="status" data-attendance-alert>
            @if ($pCan['decide'])
                {{ trans_choice('hr_attendance.timesheets.alert', $pTotals['to_decide'], ['count' => $pTotals['to_decide']]) }}
                <span class="font-normal {{ $muted }}">{{ __('hr_attendance.timesheets.pick') }}</span>
            @else
                {{ trans_choice('hr_attendance.timesheets.alert-own', $pTotals['to_decide'], ['count' => $pTotals['to_decide']]) }}
            @endif
        </p>
    @endif

    {{-- The month as a calendar: words and shape, never colour alone --}}
    <div class="grid grid-cols-7 gap-1 sm:gap-1.5" data-attendance-calendar>
        @foreach (__('hr_attendance.timesheets.weekdays') as $weekday)
            <div class="pb-1 text-center text-xs font-semibold {{ $muted }}">{{ $weekday }}</div>
        @endforeach
        @for ($i = 0; $i < $lead; $i++)
            <div aria-hidden="true"></div>
        @endfor

        @foreach ($pSheet['rows'] as $row)
            @php
                $kind = $row['kind'];
                $canAnswer = $row['decidable'] && $pCan['decide'];
                $isSelected = $selectedDate === $row['date']->toDateString();
                $label = __('hr_attendance.timesheets.status.'.$kind).' · '.$row['date']->format('d/m');
                $box = match ($kind) {
                    Sheet::ONE_PUNCH, Sheet::NO_RECORD => 'border-primary bg-primary text-white dark:border-brand dark:bg-brand',
                    Sheet::WEEKEND, Sheet::HOLIDAY => 'border-transparent bg-zinc-100 text-zinc-500 dark:bg-white/[0.04] dark:text-ink-muted',
                    Sheet::LEAVE => 'border-panel-border bg-white dark:bg-surface-1',
                    Sheet::DECIDED_ABSENT => 'border-dashed border-secondary-medium bg-white dark:bg-surface-1',
                    Sheet::OUTSIDE, Sheet::UPCOMING => 'border-dashed border-panel-border bg-white text-zinc-500 dark:bg-surface-1 dark:text-ink-muted',
                    default => 'border-panel-border bg-white dark:bg-surface-1',
                };
                $classes = 'flex min-h-16 flex-col items-start gap-0.5 rounded-md border p-1.5 text-start sm:min-h-20 sm:p-2 '.$box;
            @endphp
            @if ($canAnswer)
                <button type="button" wire:click="openDay({{ $pEmployee->id }}, '{{ $row['date']->toDateString() }}')" aria-label="{{ $label }}" aria-pressed="{{ $isSelected ? 'true' : 'false' }}" data-attendance-day="{{ $row['date']->toDateString() }}"
                    @class([$classes, 'transition hover:-translate-y-0.5', 'outline-2 outline-offset-2 outline-secondary-dark dark:outline-ink' => $isSelected])>
            @else
                <div title="{{ $label }}" data-attendance-cell="{{ $row['date']->toDateString() }}" class="{{ $classes }}">
            @endif
                <span class="text-sm font-semibold leading-none tabular-nums">{{ $row['date']->day }}</span>
                <span class="mt-auto w-full text-xs font-semibold leading-tight">
                    @switch($kind)
                        @case(Sheet::WORKED)
                            <span class="hidden tabular-nums sm:block {{ $muted }}" dir="ltr">{{ $hm($row['first']) }}–{{ $hm($row['last']) }}</span>
                            @if ($row['worked_minutes'])
                                <span class="tabular-nums">{{ intdiv($row['worked_minutes'], 60) }}{{ __('hr_attendance.timesheets.h') }} {{ $row['worked_minutes'] % 60 }}</span>
                            @endif
                            @break
                        @case(Sheet::ONE_PUNCH)
                            <span class="tabular-nums" dir="ltr">{{ $hm($row['first']) }} – ?</span>
                            @break
                        @case(Sheet::NO_RECORD)
                            {{ __('hr_attendance.timesheets.status.no_record') }}
                            @break
                        @case(Sheet::DECIDED_WORKED)
                            {{ __('hr_attendance.timesheets.yes') }}
                            @break
                        @case(Sheet::DECIDED_ABSENT)
                            {{ __('hr_attendance.timesheets.no') }}
                            @break
                        @case(Sheet::LEAVE)
                            {{ ! empty($row['leave_pending']) ? __('hr_attendance.timesheets.leave-waits') : __('hr_attendance.timesheets.status.leave') }}
                            @break
                        @case(Sheet::HOLIDAY)
                            <span class="line-clamp-2">{{ $row['note'] ?: __('hr_attendance.timesheets.status.holiday') }}</span>
                            @break
                    @endswitch
                    {{-- Worked on a day off: the punches are there and count as overtime, so show them. --}}
                    @if (in_array($kind, [Sheet::WEEKEND, Sheet::HOLIDAY], true) && $row['first'])
                        <span class="hidden tabular-nums sm:block {{ $muted }}" dir="ltr">{{ $hm($row['first']) }}–{{ $hm($row['last']) }}</span>
                        <span class="font-semibold">{{ __('hr_attendance.timesheets.worked-off') }}</span>
                    @endif
                </span>
                <span class="sr-only">{{ $label }}</span>
            @if ($canAnswer)
                </button>
            @else
                </div>
            @endif
        @endforeach
    </div>

    {{-- The question for the chosen day: two buttons --}}
    @if ($selected)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border-2 border-secondary-dark px-4 py-3 dark:border-ink" role="group" aria-labelledby="attendance-day-title" data-attendance-day-dialog>
            <div>
                <p id="attendance-day-title" class="font-semibold"><bdi class="tabular-nums">{{ $selected['date']->locale(app()->getLocale())->isoFormat('dddd D/M') }}</bdi></p>
                <p class="text-sm {{ $muted }}">
                    @if (in_array($selected['kind'], $pending, true))
                        {{ $selected['kind'] === Sheet::ONE_PUNCH && $selected['first'] ? __('hr_attendance.timesheets.one-at', ['time' => $selected['first']->format('H:i')]) : __('hr_attendance.timesheets.none-at') }}
                        {{ __('hr_attendance.timesheets.did-work') }}
                    @else
                        {{ __($selected['kind'] === Sheet::DECIDED_ABSENT ? 'hr_attendance.timesheets.answered-absent' : 'hr_attendance.timesheets.answered-worked') }}
                    @endif
                </p>
            </div>
            <div class="flex w-full gap-2 sm:w-auto">
                <x-ui.button class="flex-1" icon="check" wire:click="answerDay('worked')" data-test="attendance-answer-worked">{{ __('hr_attendance.timesheets.yes') }}</x-ui.button>
                <x-ui.button class="flex-1" icon="x-mark" wire:click="answerDay('absent')" data-test="attendance-answer-absent">{{ __('hr_attendance.timesheets.no') }}</x-ui.button>
                @if ($selected['decision'])
                    <x-ui.button tone="ghost" icon="trash" wire:click="clearDecision" data-test="attendance-clear-decision">{{ __('hr_attendance.timesheets.remove') }}</x-ui.button>
                @endif
            </div>
        </div>
    @endif

    {{-- The one next step for the month --}}
    <div class="flex flex-wrap items-start justify-between gap-4 border-t border-panel-border pt-4" data-attendance-month-actions>
        <div class="grid gap-2">
            @if ($panel['state'] === 'locked')
                <x-ui.tone-badge tone="good" icon="lock-closed">{{ __('hr_attendance.timesheets.closed') }}</x-ui.tone-badge>
                <p class="text-sm {{ $muted }}">{{ __('hr_attendance.timesheets.closed-note', ['date' => $pRecord->locked_at?->format('d/m/Y')]) }}</p>
            @elseif ($panel['state'] === 'approved')
                <x-ui.tone-badge tone="good" icon="check">{{ __('hr_attendance.timesheets.confirmed') }}</x-ui.tone-badge>
                <p class="text-sm {{ $muted }}">{{ __('hr_attendance.timesheets.confirmed-by', ['name' => $pRecord->approver?->name ?? '—', 'date' => $pRecord->approved_at?->format('d/m/Y')]) }}</p>
            @elseif ($pCan['approve'])
                <div>
                    <x-ui.button tone="primary" icon="check" wire:click="approveMonth({{ $pEmployee->id }})" data-test="attendance-approve">{{ __('hr_attendance.timesheets.confirm-month') }}</x-ui.button>
                </div>
                <p class="max-w-prose text-sm {{ $muted }}">{{ __('hr_attendance.timesheets.confirm-why') }}</p>
            @elseif ($pCan['confirm'])
                <div>
                    <x-ui.button tone="primary" icon="check" wire:click="confirmMonth({{ $pEmployee->id }})" data-test="attendance-confirm">{{ __('hr_attendance.timesheets.confirm-own') }}</x-ui.button>
                </div>
            @elseif ($pTotals['to_decide'] > 0 && $pCan['decide'])
                <p class="text-sm {{ $muted }}">{{ __('hr_attendance.timesheets.answer-first') }}</p>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($pCan['lock'])
                <x-ui.button size="sm" icon="lock-closed" wire:click="lockMonth({{ $pEmployee->id }})" data-test="attendance-lock">{{ __('hr_attendance.workflow.lock') }}</x-ui.button>
            @endif
            @if ($pCan['reopen'])
                <x-ui.button size="sm" wire:click="reopenMonth({{ $pEmployee->id }})" wire:confirm="{{ __('hr_attendance.workflow.reopen') }}?" data-test="attendance-reopen">{{ __('hr_attendance.workflow.reopen') }}</x-ui.button>
            @endif
        </div>
    </div>

    {{-- The signed paper: HR's, folded away --}}
    @if ($pCan['signed'] || $panelSigned)
        <details class="rounded-lg border border-panel-border px-4 py-3" data-attendance-signed>
            <summary class="min-h-9 cursor-pointer text-sm font-semibold">{{ __('hr_attendance.workflow.signed') }}@if ($panelSigned) ✓@endif</summary>
            <div class="mt-3 grid gap-y-2">
                @if ($panelSigned)
                    <div class="flex flex-wrap items-center gap-3">
                        <a class="text-sm font-semibold underline" target="_blank" href="{{ route('hr.attendance.signed', $pRecord->id) }}">{{ __('hr_attendance.workflow.signed-view') }}</a>
                        @if ($pCan['signed'])
                            <x-ui.button tone="ghost" size="sm" wire:click="removeSigned" data-test="attendance-signed-remove">{{ __('hr_attendance.workflow.signed-remove') }}</x-ui.button>
                        @endif
                    </div>
                @else
                    <p class="text-sm {{ $muted }}">{{ __('hr_attendance.workflow.signed-none') }}</p>
                @endif
                @if ($pCan['signed'])
                    <x-ui.form-file wire:model="signedFile" :label="__('hr_attendance.workflow.signed-upload')" :help="__('hr_attendance.workflow.signed-help')" accept=".pdf,.jpg,.jpeg,.png" />
                    <div>
                        <x-ui.button size="sm" wire:click="saveSigned" data-test="attendance-signed-save">{{ __('hr_attendance.workflow.signed-attach') }}</x-ui.button>
                    </div>
                @endif
            </div>
        </details>
    @endif
</div>
