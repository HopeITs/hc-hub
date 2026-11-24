<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Core\Models\Program;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Recruitment\Access;
use Modules\Hr\Support\Reports\LeavingReasonsReport;
use Modules\Hr\Support\Reports\ReportCatalog;
use Modules\Hr\Support\Reports\ReportCsv;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Why people leave: the termination reason recorded on the contract of each
 * person who left, one row per person. HR (their city) and the head-office
 * roles only: a manager reaches their team's contracts elsewhere, but not this
 * report. Figures come from LeavingReasonsReport, which the CSV export reads too.
 */
new #[Layout('layouts::desk'), Title('hr_reports.reports.leaving-reasons.title')] class extends Component {
    #[Url(as: 'period')]
    public int $days = 365;

    /** @var array<string, int|string|null> */
    #[Url(as: 'f')]
    public array $filters = [];

    public function mount(): void
    {
        $this->authorizeReport();
    }

    public function clearFilters(): void
    {
        $this->filters = [];
    }

    public function export(): StreamedResponse
    {
        $this->authorizeReport();
        $r = $this->report()->build();
        $share = fn ($rows): array => $rows->map(fn (array $x): array => [$x['label'], $x['count'], $x['percent'].'%'])->all();
        $t = fn (string $key): string => __('hr_reports.csv.'.$key);

        return ReportCsv::download('leaving-reasons-'.now()->format('Y-m-d').'.csv', [
            ['title' => $t('summary'), 'headings' => [$t('metric'), $t('value')], 'rows' => [
                [__('hr_reports.leaving.total'), $r['total']],
                [__('hr_reports.leaving.recorded'), $r['recorded']],
                [__('hr_reports.leaving.reason_missing'), $r['missing']],
                [__('hr_reports.leaving.average_tenure'), $r['averageTenureMonths'] ?? '-'],
                [__('hr_reports.leaving.previous_period'), $r['previousTotal'] ?? '-'],
                [__('hr_reports.leaving.turnover'), $r['turnoverPercent'] === null ? '-' : $r['turnoverPercent'].'%'],
            ]],
            ['title' => __('hr_reports.leaving.by_category'), 'headings' => [$t('category'), $t('people'), $t('share')], 'rows' => $share($r['byCategory'])],
            ['title' => __('hr_reports.leaving.by_reason'), 'headings' => [$t('reason'), $t('people'), $t('share')], 'rows' => $share($r['byReason'])],
            ['title' => __('hr_reports.leaving.by_tenure'), 'headings' => [$t('tenure'), $t('people'), $t('share')], 'rows' => $share($r['byTenure'])],
            ['title' => __('hr_reports.leaving.by_city'), 'headings' => [$t('city'), $t('people'), $t('share')], 'rows' => $share($r['byCity'])],
            ['title' => __('hr_reports.leaving.by_month'), 'headings' => [$t('month'), $t('people')], 'rows' => $r['monthly']->map(fn (int $n, string $m): array => [$m, $n])->values()->all()],
            ['title' => __('hr_reports.leaving.without_reason'), 'headings' => [$t('name'), $t('left_on'), $t('note')], 'rows' => array_map(fn (array $x): array => [$x['name'], $x['left_on'], $x['note'] ?? ''], $r['withoutReason'])],
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
     * id => label, per filter.
     *
     * @return array<string, array<int, string>>
     */
    #[Computed]
    public function options(): array
    {
        return [
            'department_id' => Department::query()->orderBy('name')->pluck('name', 'id')->all(),
            'program_id' => Program::namesById(),
            'job_level_id' => JobLevel::query()->orderBy('grade')->get()->pluck('name', 'id')->all(),
            'position_id' => Position::query()->orderBy('title_en')->get()->pluck('title', 'id')->all(),
            'contract_type_id' => ContractType::query()->orderBy('id')->get()->pluck('name', 'id')->all(),
        ];
    }

    #[Computed]
    public function canEditContracts(): bool
    {
        return Access::hasActivePermission(Auth::user(), 'contracts.update');
    }

    private function report(): LeavingReasonsReport
    {
        return new LeavingReasonsReport(Auth::user(), $this->days, $this->filters);
    }

    private function authorizeReport(): void
    {
        abort_unless(ReportCatalog::allows('leaving-reasons', Auth::user()), 403);
    }
}; ?>

@php
    $r = $this->r;
    $filterKeys = ['department_id', 'program_id', 'job_level_id', 'position_id', 'contract_type_id'];
    $hasFilters = collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty();
    $delta = $r['previousTotal'] === null ? null : $r['total'] - $r['previousTotal'];
    $panels = [['by_category', $r['byCategory']], ['by_reason', $r['byReason']], ['by_tenure', $r['byTenure']], ['by_city', $r['byCity']]];
@endphp

<x-desk.register class="@container" heading-id="hr-report-leaving-heading" :title="__('hr_reports.reports.leaving-reasons.title')" :count="$r['total']">
    <x-slot:figures>{{ __('hr_reports.reports.leaving-reasons.lead') }}</x-slot:figures>

    <x-slot:filters>
        <x-ui.header-filter-select id="leaving-period" :label="__('hr_reports.period')" wire:model.live="days" :value="(string) $r['days']">
            @foreach (LeavingReasonsReport::PERIODS as $period)
                <option value="{{ $period }}">{{ $period === 0 ? __('hr_reports.all_time') : __('hr_reports.last_days', ['n' => $period]) }}</option>
            @endforeach
        </x-ui.header-filter-select>

        @foreach ($filterKeys as $key)
            <x-ui.header-filter-select id="leaving-filter-{{ $key }}" :label="__('hr_reports.leaving.filters.'.$key)" wire:model.live="filters.{{ $key }}" :value="(string) ($filters[$key] ?? '')">
                <option value="">{{ __('hr_reports.all') }}</option>
                @foreach ($this->options[$key] as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-ui.header-filter-select>
        @endforeach

        @if ($hasFilters)
            <x-ui.button tone="ghost" icon="x-mark" size="sm" wire:click="clearFilters">{{ __('hr_reports.clear_filters') }}</x-ui.button>
        @endif
    </x-slot:filters>

    <x-slot:actions>
        <x-ui.button tone="action" icon="arrow-down-tray" wire:click="export" data-test="export-report">{{ __('hr_reports.export') }}</x-ui.button>
    </x-slot:actions>

    <div class="grid gap-4" wire:loading.class="opacity-60">
        <div class="grid grid-cols-2 gap-4 @3xl:grid-cols-4">
            <x-ui.stat-tile :label="__('hr_reports.leaving.total')" :value="number_format($r['total'])" icon="users" data-testid="leaving-total" />
            <x-ui.stat-tile :label="__('hr_reports.leaving.turnover')" :value="$r['turnoverPercent'] === null ? '-' : $r['turnoverPercent'].'%'" icon="chart-bar" data-testid="leaving-turnover" />
            <x-ui.stat-tile :label="__('hr_reports.leaving.recorded')" :value="number_format($r['recorded'])" icon="document-text" tone="good" />
            <x-ui.stat-tile :label="__('hr_reports.leaving.average_tenure')" :value="$r['averageTenureMonths'] ?? '-'" icon="clock" />
        </div>

        <p class="type-meta text-secondary-medium dark:text-ink-muted">
            @if ($delta !== null)
                <span data-testid="leaving-delta">
                    @if ($delta === 0)
                        {{ __('hr_reports.leaving.same_as_previous', ['n' => $r['previousTotal']]) }}
                    @else
                        <bdi dir="ltr">{{ $delta > 0 ? '+' : '' }}{{ $delta }}</bdi> {{ __('hr_reports.leaving.vs_previous', ['n' => $r['previousTotal']]) }}
                    @endif
                </span>
                &middot;
            @endif
            {{ $r['headcount'] === null ? __('hr_reports.leaving.turnover_all_time') : __('hr_reports.leaving.average_team', ['n' => $r['headcount']]) }}
            &middot; {{ __('hr_reports.leaving.without_n', ['n' => $r['missing']]) }}
        </p>

        @if ($r['total'] === 0)
            <x-ui.panel>
                <p class="p-6 text-center type-meta text-secondary-medium dark:text-ink-muted">{{ __('hr_reports.leaving.nobody') }}</p>
            </x-ui.panel>
        @else
            <div class="grid gap-4 @3xl:grid-cols-2">
                @foreach ($panels as [$heading, $rows])
                    <x-ui.panel :title="__('hr_reports.leaving.'.$heading)" wire:key="leaving-panel-{{ $heading }}">
                        <div class="p-4"><x-hr::report.bars :rows="$rows" :empty="__('hr_reports.leaving.no_reasons')" /></div>
                    </x-ui.panel>
                @endforeach
            </div>

            <x-ui.panel :title="__('hr_reports.leaving.by_month')">
                <div class="p-4"><x-hr::report.months :months="$r['monthly']" /></div>
            </x-ui.panel>

            @if ($r['missing'] > 0)
                <x-ui.panel :title="__('hr_reports.leaving.without_reason')">
                    <p class="px-4 pt-3 type-meta text-secondary-medium dark:text-ink-muted">{{ __('hr_reports.leaving.add_reason_hint') }}</p>
                    <ul class="divide-y divide-panel-border" data-testid="leaving-missing">
                        @foreach ($r['withoutReason'] as $row)
                            <li class="flex items-baseline justify-between gap-2 px-4 py-2 text-sm" wire:key="leaving-gap-{{ $row['contract_id'] }}">
                                <span class="min-w-0 text-secondary-dark dark:text-ink">
                                    @if ($this->canEditContracts)
                                        <a href="{{ route('hr.contracts.edit', $row['contract_id']) }}" class="underline" wire:navigate>{{ $row['name'] }}</a>
                                    @else
                                        {{ $row['name'] }}
                                    @endif
                                    @if ($row['note'])
                                        <span class="text-secondary-medium dark:text-ink-muted">&middot; {{ $row['note'] }}</span>
                                    @endif
                                </span>
                                <span class="shrink-0 tabular-nums text-secondary-medium dark:text-ink-muted">{{ $row['left_on'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </x-ui.panel>
            @endif
        @endif
    </div>
</x-desk.register>
