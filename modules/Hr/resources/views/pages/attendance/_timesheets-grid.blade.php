{{--
    The month's summary, the key to the squares and the people, city by city
    and project (or department) by project. Included by ⚡timesheets.
--}}
@php
    use Modules\Hr\Models\MailList;
    use Modules\Hr\Support\Attendance\Grouping;
    use Modules\Hr\Support\Attendance\Timesheet as Sheet;

    // One square per day: icon + words + shape, never colour alone.
    $cell = [
        Sheet::WORKED => ['bg-secondary-soft text-secondary-dark dark:bg-surface-2 dark:text-ink', 'check'],
        Sheet::DECIDED_WORKED => ['bg-secondary-soft text-secondary-dark dark:bg-surface-2 dark:text-ink', 'check'],
        Sheet::ONE_PUNCH => ['border-2 border-secondary-dark bg-white text-secondary-dark dark:border-ink dark:bg-surface-1 dark:text-ink', 'exclamation-triangle'],
        Sheet::NO_RECORD => ['bg-primary text-white dark:bg-brand', 'question-mark-circle'],
        Sheet::LEAVE => ['border border-panel-border bg-white text-secondary-medium dark:bg-surface-1 dark:text-ink-muted', 'calendar'],
        Sheet::DECIDED_ABSENT => ['border border-dashed border-secondary-medium bg-white text-secondary-medium dark:bg-surface-1 dark:text-ink-muted', 'x-mark'],
        Sheet::WEEKEND => ['bg-zinc-100 text-zinc-400 dark:bg-white/[0.04] dark:text-ink-muted', null],
        Sheet::HOLIDAY => ['bg-zinc-100 text-zinc-400 dark:bg-white/[0.04] dark:text-ink-muted', null],
        Sheet::OUTSIDE => ['border border-dashed border-panel-border text-zinc-400 dark:text-ink-muted', null],
    ];
    $stateColor = ['open' => 'zinc', 'confirmed' => 'amber', 'approved' => 'sky', 'locked' => 'green'];
    $hm = fn (?int $minutes): string => $minutes === null ? '' : intdiv($minutes, 60).':'.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT);
    $groupLabel = $by === Grouping::DEPARTMENT ? 'hr_attendance.timesheets.no-department' : 'hr_attendance.timesheets.no-project';
@endphp

<div class="grid gap-3 @md:grid-cols-3" data-attendance-summary>
    <x-ui.stat-tile :label="__('hr_attendance.timesheets.people')" :value="$summary['people']" icon="users" />
    <x-ui.stat-tile :label="__('hr_attendance.timesheets.decide')" :value="$summary['to_decide']" icon="question-mark-circle" :tone="$summary['to_decide'] > 0 ? 'critical' : 'good'"
        :scope="$summary['to_decide'] > 0 ? __('hr_attendance.timesheets.to-decide-help') : __('hr_attendance.timesheets.all-decided')" />
    <x-ui.stat-tile :label="__('hr_attendance.timesheets.overtime')" :value="$summary['overtime_hours']" :unit="__('hr_attendance.timesheets.hours-unit')" icon="clock"
        :scope="__('hr_attendance.timesheets.free-days-count', ['days' => $summary['overtime_days']])" />
</div>

@if ($summary['ready_to_approve'] > 0 || $summary['ready_to_lock'] > 0)
    <div class="mt-3 flex flex-wrap items-center gap-2" data-attendance-bulk>
        @if ($summary['ready_to_approve'] > 0)
            <x-ui.button tone="primary" size="sm" icon="check" wire:click="approveAllReady" wire:confirm="{{ __('hr_attendance.workflow.approve-all', ['count' => $summary['ready_to_approve']]) }}?" data-test="attendance-approve-all">
                {{ __('hr_attendance.workflow.approve-all', ['count' => $summary['ready_to_approve']]) }}
            </x-ui.button>
        @endif
        @if ($summary['ready_to_lock'] > 0)
            <x-ui.button size="sm" icon="lock-closed" wire:click="lockAllApproved" wire:confirm="{{ __('hr_attendance.workflow.lock-all', ['count' => $summary['ready_to_lock']]) }}?" data-test="attendance-lock-all">
                {{ __('hr_attendance.workflow.lock-all', ['count' => $summary['ready_to_lock']]) }}
            </x-ui.button>
        @endif
    </div>
@endif

<ul class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs {{ $muted }}" aria-label="{{ __('hr_attendance.timesheets.key') }}">
    @foreach ([Sheet::WORKED => 'legend-worked', Sheet::ONE_PUNCH => 'legend-one', Sheet::NO_RECORD => 'legend-none', Sheet::LEAVE => 'legend-leave', Sheet::DECIDED_ABSENT => 'legend-absent', Sheet::WEEKEND => 'legend-off', Sheet::OUTSIDE => 'legend-out'] as $kind => $legend)
        <li class="inline-flex items-center gap-2">
            <span class="grid size-5 place-items-center rounded {{ $cell[$kind][0] }}">@if ($cell[$kind][1])<flux:icon :name="$cell[$kind][1]" class="size-3" />@endif</span>
            {{ __('hr_attendance.timesheets.'.$legend) }}
        </li>
    @endforeach
</ul>

@forelse ($blocks as $block)
    <section class="mt-6 space-y-3" aria-labelledby="attendance-city-{{ $block['id'] }}" wire:key="attendance-city-{{ $block['id'] }}" data-attendance-city="{{ $block['id'] }}">
        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <h2 id="attendance-city-{{ $block['id'] }}" class="text-lg font-semibold">{{ $block['name'] ?? '—' }}</h2>
            <span class="text-sm {{ $muted }}">{{ trans_choice('hr_attendance.timesheets.people-count', $block['totals']['people'], ['count' => $block['totals']['people']]) }}</span>
            @if ($block['totals']['to_decide'] > 0)
                <flux:badge size="sm" color="red" icon="exclamation-triangle">{{ trans_choice('hr_attendance.timesheets.to-decide', $block['totals']['to_decide'], ['count' => $block['totals']['to_decide']]) }}</flux:badge>
            @endif
            {{-- The mailing list that stands for this city, when there is one. --}}
            @php
                $cityList = MailList::emailFor($block['id'] ? (int) $block['id'] : null, null, null, $mailLists);
            @endphp
            @if ($cityList)
                <a href="mailto:{{ $cityList->address }}" class="inline-flex items-center gap-1.5 text-sm font-semibold underline {{ $muted }}" data-test="attendance-email-city">
                    <flux:icon name="envelope" class="size-4" /> {{ __('hr_mail_lists.email_group') }}
                </a>
            @endif
        </div>

        @foreach ($block['groups'] as $group)
            @php
                $foldKey = $block['id'].':'.$group['key'];
                $folded = $collapsed[$foldKey] ?? $foldedByDefault;
                $groupParams = $printBase + ['city_id' => $block['id'], 'group' => $group['id'] ?? 'none'];
                // The list for this project or department (in this city, else nationwide).
                $groupList = ($group['id'] ?? null) ? MailList::emailForGroup(
                    $block['id'] ? (int) $block['id'] : null,
                    $by === Grouping::PROJECT ? (int) $group['id'] : null,
                    $by === Grouping::DEPARTMENT ? (int) $group['id'] : null,
                    $mailLists,
                ) : null;
            @endphp
            <x-ui.panel wire:key="attendance-group-{{ $foldKey }}" data-attendance-group>
                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-b border-panel-border px-4 py-2.5">
                    <button type="button" wire:click="toggleGroup('{{ $foldKey }}', {{ $folded ? 'false' : 'true' }})" aria-expanded="{{ $folded ? 'false' : 'true' }}" class="flex min-h-10 items-center gap-2 text-start">
                        <flux:icon name="chevron-down" @class(['size-4 shrink-0 transition', $muted, '-rotate-90 rtl:rotate-90' => $folded]) />
                        <span class="font-semibold" dir="auto">{{ $group['name'] ?? __($groupLabel) }}</span>
                        <span class="text-xs {{ $muted }}">{{ trans_choice('hr_attendance.timesheets.people-count', $group['totals']['people'], ['count' => $group['totals']['people']]) }}</span>
                    </button>
                    <div class="flex flex-wrap items-center gap-3">
                        @if ($group['totals']['to_decide'] > 0)
                            <flux:badge size="sm" color="red" icon="exclamation-triangle">{{ trans_choice('hr_attendance.timesheets.to-decide', $group['totals']['to_decide'], ['count' => $group['totals']['to_decide']]) }}</flux:badge>
                        @else
                            <flux:badge size="sm" color="green" icon="check">{{ __('hr_attendance.timesheets.group-ready') }}</flux:badge>
                        @endif
                        @if ($groupList)
                            <x-ui.button size="sm" icon="envelope" :href="'mailto:'.$groupList->address" data-test="attendance-email-group">{{ __('hr_mail_lists.email_group') }}</x-ui.button>
                        @endif
                        @if ($group['totals']['overtime_hours'] > 0)
                            <span class="text-xs tabular-nums {{ $muted }}">{{ __('hr_attendance.timesheets.overtime-line', ['hours' => $group['totals']['overtime_hours'], 'days' => $group['totals']['overtime_days']]) }}</span>
                        @endif
                        @unless ($own)
                            <x-ui.button tone="ghost" size="sm" icon="printer" :href="route('hr.attendance.print', $groupParams)" target="_blank" data-test="attendance-print-group">{{ __('hr_attendance.timesheets.print-group') }}</x-ui.button>
                        @endunless
                    </div>
                </div>

                @unless ($folded)
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-max text-sm">
                            <thead>
                                <tr class="text-xs {{ $muted }}">
                                    <th scope="col" class="sticky start-0 z-10 min-w-48 bg-white px-4 py-2 text-start font-semibold dark:bg-surface-1">{{ __('hr_attendance.timesheets.person') }}</th>
                                    <th scope="col" class="px-3 py-2 text-center font-semibold">{{ __('hr_attendance.timesheets.days') }}</th>
                                    <th scope="col" class="px-3 py-2 text-center font-semibold">{{ __('hr_attendance.timesheets.overtime') }}</th>
                                    <th scope="col" class="px-3 py-2 text-center font-semibold">{{ __('hr_attendance.timesheets.decide') }}</th>
                                    <th scope="col" class="px-3 py-2 text-center font-semibold">{{ __('hr_attendance.workflow.state') }}</th>
                                    @foreach ($days as $day)
                                        <th scope="col" class="px-px py-2 text-center font-semibold tabular-nums">
                                            <span class="block leading-tight">{{ $day['n'] }}</span>
                                            <span class="block text-[10px] font-normal leading-tight">{{ $day['w'] }}</span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($group['items'] as $item)
                                    @php
                                        $employee = $item['employee'];
                                        $sheet = $item['sheet'];
                                    @endphp
                                    <tr class="border-t border-panel-border" wire:key="attendance-sheet-{{ $employee->id }}" data-attendance-row="{{ $employee->id }}">
                                        <th scope="row" class="sticky start-0 z-10 bg-white px-4 py-1.5 text-start font-normal dark:bg-surface-1">
                                            <button type="button" wire:click="openMonth({{ $employee->id }})" class="block min-h-10 text-start" aria-label="{{ __('hr_attendance.timesheets.open-month', ['name' => $employee->name]) }}" data-test="attendance-open-month">
                                                <span class="block font-semibold" dir="auto">{{ $employee->name }}</span>
                                                <span class="block text-xs {{ $muted }}"><span class="tabular-nums">#{{ $employee->id }}</span>@if ($sheet['placement']['center']) · <bdi>{{ $sheet['placement']['center'] }}</bdi>@endif</span>
                                            </button>
                                        </th>
                                        <td class="px-3 text-center font-semibold tabular-nums">{{ $sheet['totals']['working_days'] }}</td>
                                        <td class="whitespace-nowrap px-3 text-center text-xs tabular-nums">
                                            @if ($sheet['totals']['overtime_hours'] > 0)
                                                {{ __('hr_attendance.timesheets.overtime-line', ['hours' => $sheet['totals']['overtime_hours'], 'days' => $sheet['totals']['overtime_days']]) }}
                                            @else
                                                <span class="{{ $muted }}">–</span>
                                            @endif
                                        </td>
                                        <td class="px-3 text-center">
                                            @if ($sheet['totals']['to_decide'] > 0)
                                                <flux:badge size="sm" color="red">{{ $sheet['totals']['to_decide'] }}</flux:badge>
                                            @else
                                                <span class="{{ $muted }}">–</span>
                                            @endif
                                        </td>
                                        <td class="px-3 text-center">
                                            <flux:badge size="sm" :color="$stateColor[$item['state']]">{{ __('hr_attendance.workflow.states.'.$item['state']) }}</flux:badge>
                                        </td>
                                        @foreach ($sheet['rows'] as $row)
                                            @php
                                                [$classes, $icon] = $cell[$row['kind']];
                                                $label = __('hr_attendance.timesheets.status.'.$row['kind']).' · '.$row['date']->format('d/m').($row['worked_minutes'] ? ' · '.$hm($row['worked_minutes']) : '');
                                            @endphp
                                            <td class="px-px py-1">
                                                @if ($row['decidable'] && $item['can']['decide'])
                                                    <button type="button" wire:click="openDay({{ $employee->id }}, '{{ $row['date']->toDateString() }}')" title="{{ $label }}" aria-label="{{ $employee->name }}: {{ $label }}"
                                                        class="grid size-7 place-items-center rounded transition hover:scale-110 {{ $classes }}" data-attendance-day="{{ $row['date']->toDateString() }}">
                                                        @if ($icon)<flux:icon :name="$icon" class="size-3.5" />@endif
                                                    </button>
                                                @else
                                                    <span title="{{ $label }}" class="grid size-7 place-items-center rounded {{ $classes }}">
                                                        @if ($icon)<flux:icon :name="$icon" class="size-3.5" />@endif
                                                        <span class="sr-only">{{ $label }}</span>
                                                    </span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endunless
            </x-ui.panel>
        @endforeach
    </section>
@empty
    <x-ui.empty-state class="mt-6" icon="clock" :heading="__('hr_attendance.timesheets.nothing')" data-attendance-empty />
@endforelse
