{{--
    Everyone, one line each: a name, the days worked of the days planned and one
    status in plain words. Red is only for days that wait for an answer. Included
    by ⚡timesheets.
--}}
@php
    use Modules\Hr\Models\Employee;
    use Modules\Hr\Models\MailList;
    use Modules\Hr\Support\Attendance\Grouping;
    use Modules\Hr\Support\Attendance\Timesheet as Sheet;

    $groupLabel = $by === Grouping::DEPARTMENT ? 'hr_attendance.timesheets.no-department' : 'hr_attendance.timesheets.no-project';
    $planned = fn (array $sheet): int => collect($sheet['rows'])->whereNotIn('kind', [Sheet::WEEKEND, Sheet::HOLIDAY, Sheet::OUTSIDE])->count();
    $stateChip = fn (string $state, bool $canApprove): array => match (true) {
        $state === 'locked' => ['good', 'lock-closed', __('hr_attendance.timesheets.closed')],
        $state === 'approved' => ['good', 'check', __('hr_attendance.timesheets.confirmed')],
        $canApprove => ['warn', 'clock', __('hr_attendance.timesheets.wait-confirm')],
        default => ['neutral', 'clock', __('hr_attendance.timesheets.in-progress')],
    };
@endphp

<div class="flex flex-wrap gap-1 border-b border-panel-border" role="tablist" aria-label="{{ __('hr_attendance.timesheets.tab-all') }}" data-attendance-tabs>
    @foreach (['all' => 'tab-all', 'need' => 'tab-need', 'done' => 'tab-done'] as $key => $label)
        <button type="button" role="tab" wire:click="$set('tab', '{{ $key }}')" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" data-test="attendance-tab-{{ $key }}"
            @class(['-mb-px flex min-h-11 items-center gap-2 border-b-2 px-3 text-sm font-semibold', 'border-secondary-dark dark:border-ink' => $tab === $key, 'border-transparent '.$muted => $tab !== $key])>
            {{ __('hr_attendance.timesheets.'.$label) }}
            <x-ui.tone-badge :tone="$key === 'need' && $counts['need'] > 0 ? 'critical' : 'neutral'">{{ $counts[$key] }}</x-ui.tone-badge>
        </button>
    @endforeach
</div>

{{-- Month-end in one line: confirm everyone who is ready --}}
@if ($summary['ready_to_approve'] > 0 || $summary['ready_to_lock'] > 0)
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-panel-border px-4 py-3" data-attendance-bulk>
        <p class="text-sm font-semibold">
            @if ($summary['ready_to_approve'] > 0)
                {{ trans_choice('hr_attendance.timesheets.ready-help', $summary['ready_to_approve'], ['count' => $summary['ready_to_approve']]) }}
            @endif
        </p>
        <div class="flex flex-wrap gap-2">
            @if ($summary['ready_to_approve'] > 0)
                <x-ui.confirm-action :title="__('hr_attendance.timesheets.confirm-why')" confirm="approveAllReady" :confirm-label="__('hr_attendance.timesheets.confirm-all', ['count' => $summary['ready_to_approve']])" tone="neutral">
                    <x-ui.button tone="primary" size="sm" icon="check" data-test="attendance-approve-all">
                        {{ __('hr_attendance.timesheets.confirm-all', ['count' => $summary['ready_to_approve']]) }}
                    </x-ui.button>
                </x-ui.confirm-action>
            @endif
            @if ($summary['ready_to_lock'] > 0)
                <x-ui.confirm-action :title="__('hr_attendance.workflow.lock-all', ['count' => $summary['ready_to_lock']]) . '?'" confirm="lockAllApproved" :confirm-label="__('hr_attendance.workflow.lock-all', ['count' => $summary['ready_to_lock']])" tone="neutral">
                    <x-ui.button size="sm" icon="lock-closed" data-test="attendance-lock-all">
                        {{ __('hr_attendance.workflow.lock-all', ['count' => $summary['ready_to_lock']]) }}
                    </x-ui.button>
                </x-ui.confirm-action>
            @endif
        </div>
    </div>
@endif

@forelse ($blocks as $block)
    <section class="mt-6 space-y-3" aria-labelledby="attendance-city-{{ $block['id'] }}" wire:key="attendance-city-{{ $block['id'] }}" data-attendance-city="{{ $block['id'] }}">
        @if (count($blocks) > 1 || ! $cityId)
            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <h2 id="attendance-city-{{ $block['id'] }}" class="text-lg font-semibold">{{ $block['name'] ?? '—' }}</h2>
                {{-- The mailing list that stands for this city, when there is one. --}}
                @php
                    $cityList = MailList::emailFor($block['id'] ? (int) $block['id'] : null, null, null, $mailLists);
                @endphp
                @if ($cityList)
                    <a href="{{ $cityList->mailto() }}" class="inline-flex items-center gap-1.5 text-sm font-semibold underline {{ $muted }}" data-test="attendance-email-city">
                        <flux:icon name="envelope" class="size-4" /> {{ __('hr_mail_lists.email_group') }}
                    </a>
                @endif
            </div>
        @endif

        @foreach ($block['groups'] as $group)
            @php
                $foldKey = $block['id'].':'.$group['key'];
                // While a search or a filter is on every group is open, whatever was folded by hand,
                // so a match is never hidden; clearing it brings back what was chosen.
                $folded = ! $searching && ($collapsed[$foldKey] ?? $foldedByDefault);
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
                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-4 py-2.5">
                    <button type="button" wire:click="toggleGroup('{{ $foldKey }}', {{ $folded ? 'false' : 'true' }})" aria-expanded="{{ $folded ? 'false' : 'true' }}" class="flex min-h-10 items-center gap-2 text-start">
                        <flux:icon name="chevron-down" @class(['size-4 shrink-0 transition', $muted, '-rotate-90 rtl:rotate-90' => $folded]) />
                        <span class="font-semibold" dir="auto">{{ $group['name'] ?? __($groupLabel) }}</span>
                        <span class="text-xs {{ $muted }}">{{ trans_choice('hr_attendance.timesheets.people-count', $group['totals']['people'], ['count' => $group['totals']['people']]) }}</span>
                    </button>
                    <div class="flex flex-wrap items-center gap-3">
                        @if ($groupList)
                            <x-ui.button size="sm" icon="envelope" :href="$groupList->mailto()" data-test="attendance-email-group">{{ __('hr_mail_lists.email_group') }}</x-ui.button>
                        @endif
                        @unless ($own || $printBlocked)
                            <x-ui.button tone="ghost" size="sm" icon="printer" :href="route('hr.attendance.print', $groupParams)" target="_blank" data-test="attendance-print-group">{{ __('hr_attendance.timesheets.print-group') }}</x-ui.button>
                        @endunless
                    </div>
                </div>

                @unless ($folded)
                    <ul class="divide-y divide-panel-border border-t border-panel-border">
                        @foreach ($group['items'] as $item)
                            @php
                                $employee = $item['employee'];
                                $sheet = $item['sheet'];
                                [$chipTone, $chipIcon, $chipLabel] = $stateChip($item['state'], (bool) $item['can']['approve']);
                            @endphp
                            <li wire:key="attendance-sheet-{{ $employee->id }}" data-attendance-row="{{ $employee->id }}">
                                <button type="button" wire:click="openMonth({{ $employee->id }})" class="flex w-full items-center gap-3 px-4 py-3 text-start hover:bg-secondary-soft/50 dark:hover:bg-white/[0.04]" aria-label="{{ __('hr_attendance.timesheets.open-month', ['name' => $employee->name]) }}" data-test="attendance-open-month">
                                    <x-ui.avatar :src="Employee::photoUrl($employee->id)" class="size-9 rounded-full" :size="36" />
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate font-semibold" dir="auto">{{ $employee->name }}</span>
                                        <span class="block text-xs {{ $muted }}"><span class="tabular-nums">#{{ $employee->id }}</span>@if ($sheet['placement']['center']) · <bdi>{{ $sheet['placement']['center'] }}</bdi>@endif</span>
                                    </span>
                                    <span class="hidden text-sm tabular-nums sm:block {{ $muted }}">{{ __('hr_attendance.timesheets.days-of', ['worked' => $sheet['totals']['working_days'] + 0, 'planned' => $planned($sheet)]) }}</span>
                                    @if ($sheet['totals']['to_decide'] > 0)
                                        <x-ui.tone-badge tone="critical" icon="exclamation-triangle">{{ trans_choice('hr_attendance.timesheets.need-you', $sheet['totals']['to_decide'], ['count' => $sheet['totals']['to_decide']]) }}</x-ui.tone-badge>
                                    @else
                                        <x-ui.tone-badge :tone="$chipTone" :icon="$chipIcon">{{ $chipLabel }}</x-ui.tone-badge>
                                    @endif
                                    <flux:icon name="chevron-right" class="size-4 shrink-0 rtl:rotate-180 {{ $muted }}" />
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endunless
            </x-ui.panel>
        @endforeach
    </section>
@empty
    <x-ui.empty-state class="mt-6" icon="clock" :heading="$counts['all'] === 0 ? __('hr_attendance.timesheets.no-punches') : __('hr_attendance.timesheets.nothing')" data-attendance-empty />
@endforelse
