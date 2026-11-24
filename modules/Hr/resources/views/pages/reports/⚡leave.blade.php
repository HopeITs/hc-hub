<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Hr\Models\Department;
use Modules\Hr\Support\Reports\LeaveReport;
use Modules\Hr\Support\Reports\ReportCatalog;
use Modules\Hr\Support\Reports\ReportCsv;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Leave balances and usage: the annual leave still owed on contracts in
 * force and the leave taken in the period (LeaveReport). HR and head office,
 * with leaves.view-all, inside the viewer's cities.
 */
new #[Layout('layouts::desk'), Title('hr_reports.reports.leave.title')] class extends Component {
    #[Url(as: 'period')]
    public int $days = 365;

    #[Url(as: 'department')]
    public ?string $departmentId = null;

    public function mount(): void
    {
        $this->authorizeReport();
    }

    public function export(): StreamedResponse
    {
        $this->authorizeReport();
        $r = $this->report()->build();
        $t = fn (string $key): string => __('hr_reports.csv.'.$key);
        $l = fn (string $key): string => __('hr_reports.leave.'.$key);

        return ReportCsv::download('leave-report-'.now()->format('Y-m-d').'.csv', [
            ['title' => $t('summary'), 'headings' => [$t('metric'), $t('value')], 'rows' => [
                [$l('people'), $r['people']],
                [$l('owed'), $r['owedDays']],
                [$l('average_owed'), $r['averageOwedDays'] ?? '-'],
                [$l('idle'), $r['idleCount']],
                [$l('taken'), $r['takenDays']],
                [$l('days_to_answer'), $r['daysToAnswer'] ?? '-'],
                [$l('pending'), $r['pending']],
                [$l('pending_over_three'), $r['pendingOverThree']],
            ]],
            ['title' => $l('by_department'), 'headings' => [$t('department'), $t('people'), $t('owed'), $t('average')],
                'rows' => $r['byDepartment']->map(fn (array $d): array => [$d['label'], $d['people'], $d['owed'], $d['average']])->all()],
            ['title' => $l('most_owed'), 'headings' => [$t('name'), $t('department'), $t('city'), $t('owed'), $t('used')],
                'rows' => $r['mostOwed']->map(fn (array $p): array => [$p['name'], $p['department'], $p['city'], $p['remaining'], $p['used']])->all()],
            ['title' => $l('idle'), 'headings' => [$t('name'), $t('department'), $t('city'), $t('owed')],
                'rows' => $r['idle']->map(fn (array $p): array => [$p['name'], $p['department'], $p['city'], $p['remaining']])->all()],
            ['title' => $l('by_type'), 'headings' => [$t('leave_type'), $t('days'), $t('share')],
                'rows' => $r['byType']->map(fn (array $x): array => [$x['label'], $x['count'], $x['percent'].'%'])->all()],
            ['title' => $l('by_month'), 'headings' => [$t('month'), $t('days')],
                'rows' => $r['byMonth']->map(fn (float $d, string $m): array => [$m, $d])->values()->all()],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function r(): array
    {
        $this->authorizeReport();

        return $this->report()->build();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function departments(): array
    {
        return Department::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    private function report(): LeaveReport
    {
        return new LeaveReport(Auth::user(), $this->days, filled($this->departmentId) ? (int) $this->departmentId : null);
    }

    private function authorizeReport(): void
    {
        abort_unless(ReportCatalog::allows('leave', Auth::user()), 403);
    }
}; ?>

@php
    $r = $this->r;
    $dash = fn ($value) => $value ?? '-';
@endphp

<x-desk.register class="@container" heading-id="hr-report-leave-heading" :title="__('hr_reports.reports.leave.title')">
    <x-slot:figures>{{ __('hr_reports.reports.leave.lead') }} {{ __('hr_reports.leave.balances_note') }}</x-slot:figures>

    <x-slot:filters>
        <x-ui.header-filter-select id="leave-period" :label="__('hr_reports.period')" wire:model.live="days" :value="(string) $r['days']">
            @foreach (LeaveReport::PERIODS as $period)
                <option value="{{ $period }}">{{ $period === 0 ? __('hr_reports.all_time') : __('hr_reports.last_days', ['n' => $period]) }}</option>
            @endforeach
        </x-ui.header-filter-select>

        <x-ui.header-filter-select id="leave-department" :label="__('hr_reports.department')" wire:model.live="departmentId" :value="(string) $departmentId" data-testid="leave-department">
            <option value="">{{ __('hr_reports.all') }}</option>
            @foreach ($this->departments as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </x-ui.header-filter-select>
    </x-slot:filters>

    <x-slot:actions>
        <x-ui.button tone="action" icon="arrow-down-tray" wire:click="export" data-test="export-report">{{ __('hr_reports.export') }}</x-ui.button>
    </x-slot:actions>

    <div class="grid gap-4" wire:loading.class="opacity-60">
        <div class="grid grid-cols-2 gap-4 @3xl:grid-cols-4">
            <x-ui.stat-tile :label="__('hr_reports.leave.owed')" :value="$r['owedDays']" icon="calendar-days" data-testid="leave-owed" />
            <x-ui.stat-tile :label="__('hr_reports.leave.idle')" :value="$r['idleCount']" icon="users" tone="warn" data-testid="leave-idle" />
            <x-ui.stat-tile :label="__('hr_reports.leave.taken')" :value="$r['takenDays']" icon="check-circle" data-testid="leave-taken" />
            <x-ui.stat-tile :label="__('hr_reports.leave.days_to_answer')" :value="$dash($r['daysToAnswer'])" icon="clock" />
        </div>

        <p class="type-meta text-secondary-medium dark:text-ink-muted">
            {{ __('hr_reports.leave.across', ['n' => $r['people'], 'avg' => $dash($r['averageOwedDays'])]) }}
            &middot; {{ __('hr_reports.leave.idle_after', ['n' => LeaveReport::IDLE_AFTER_DAYS]) }}
            &middot; {{ __('hr_reports.leave.waiting', ['a' => $r['pending'], 'b' => $r['pendingOverThree']]) }}
        </p>

        <div class="grid gap-4 @3xl:grid-cols-2">
            <x-ui.panel :title="__('hr_reports.leave.by_department')">
                <div class="px-4 py-2">
                    @forelse ($r['byDepartment'] as $row)
                        <div class="flex items-baseline justify-between gap-2 border-b border-panel-border py-2 text-sm last:border-0">
                            <span class="text-secondary-dark dark:text-ink">{{ $row['label'] }} <span class="text-secondary-medium dark:text-ink-muted">&middot; {{ __('hr_reports.leave.people_n', ['n' => $row['people']]) }}</span></span>
                            <span class="tabular-nums text-secondary-medium dark:text-ink-muted">{{ $row['owed'] }} <span>({{ __('hr_reports.leave.avg', ['n' => $row['average']]) }})</span></span>
                        </div>
                    @empty
                        <p class="py-2 type-meta text-secondary-medium dark:text-ink-muted">{{ __('hr_reports.nothing') }}</p>
                    @endforelse
                </div>
            </x-ui.panel>

            <x-ui.panel :title="__('hr_reports.leave.by_type')">
                <div class="p-4"><x-hr::report.bars :rows="$r['byType']" /></div>
            </x-ui.panel>
        </div>

        <div class="grid gap-4 @3xl:grid-cols-2">
            @foreach ([['most_owed', $r['mostOwed']], ['idle', $r['idle']]] as [$heading, $people])
                <x-ui.panel :title="__('hr_reports.leave.'.$heading)" wire:key="leave-people-{{ $heading }}">
                    <div class="px-4 py-2">
                        @forelse ($people as $p)
                            <div class="flex items-baseline justify-between gap-2 border-b border-panel-border py-2 text-sm last:border-0">
                                <span class="min-w-0 text-secondary-dark dark:text-ink">
                                    @if ($p['username'])
                                        <a href="{{ route('hr.employees.show', $p['username']) }}" class="underline" wire:navigate>{{ $p['name'] }}</a>
                                    @else
                                        {{ $p['name'] }}
                                    @endif
                                    <span class="text-secondary-medium dark:text-ink-muted">&middot; {{ $p['department'] }} &middot; {{ $p['city'] }}</span>
                                </span>
                                <span class="shrink-0 tabular-nums text-secondary-medium dark:text-ink-muted">{{ $p['remaining'] }}</span>
                            </div>
                        @empty
                            <p class="py-2 type-meta text-secondary-medium dark:text-ink-muted">{{ __('hr_reports.nothing') }}</p>
                        @endforelse
                    </div>
                </x-ui.panel>
            @endforeach
        </div>

        @if ($r['byMonth']->isNotEmpty())
            <x-ui.panel :title="__('hr_reports.leave.by_month')">
                <div class="p-4"><x-hr::report.months :months="$r['byMonth']" /></div>
            </x-ui.panel>
        @endif
    </div>
</x-desk.register>
