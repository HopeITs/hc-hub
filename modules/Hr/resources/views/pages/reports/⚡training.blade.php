<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Hr\Models\Course;
use Modules\Hr\Models\CourseCategory;
use Modules\Hr\Support\Reports\ReportCatalog;
use Modules\Hr\Support\Reports\ReportCsv;
use Modules\Hr\Support\Reports\TrainingReport;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Training: courses recorded on employee files, by category, month and status,
 * cost, and who has had none (TrainingReport). HR and head office with
 * employees.view; a city HR sees its own city.
 */
new #[Layout('layouts::desk'), Title('hr_reports.reports.training.title')] class extends Component {
    #[Url(as: 'year')]
    public ?string $year = null;

    #[Url(as: 'category')]
    public ?string $categoryId = null;

    public function mount(): void
    {
        $this->authorizeReport();
        $this->year ??= (string) now()->year;
    }

    public function export(): StreamedResponse
    {
        $this->authorizeReport();
        $r = $this->report()->build();
        $t = fn (string $key): string => __('hr_reports.csv.'.$key);
        $share = fn ($rows): array => $rows->map(fn (array $x): array => [$x['label'], $x['count'], $x['percent'].'%'])->all();

        return ReportCsv::download('training-report-'.now()->format('Y-m-d').'.csv', [
            ['title' => $t('summary'), 'headings' => [$t('metric'), $t('value')], 'rows' => [
                [$t('year'), $r['year'] ?? __('hr_reports.training.all_years')],
                [__('hr_reports.training.courses'), $r['courses']],
                [__('hr_reports.training.trained'), $r['trained']],
                [__('hr_reports.training.people_in_force'), $r['people']],
                [__('hr_reports.training.coverage'), $r['coveragePercent'] ?? '-'],
                [__('hr_reports.training.cost'), $r['cost']],
                [__('hr_reports.training.certificates'), $r['certificates']],
                [__('hr_reports.training.untrained'), $r['untrainedCount']],
            ]],
            ['title' => __('hr_reports.training.by_category'), 'headings' => [$t('category'), $t('courses'), $t('share')], 'rows' => $share($r['byCategory'])],
            ['title' => __('hr_reports.training.by_status'), 'headings' => [$t('status'), $t('courses'), $t('share')], 'rows' => $share($r['byStatus'])],
            ['title' => __('hr_reports.training.by_city'), 'headings' => [$t('city'), $t('courses'), $t('share')], 'rows' => $share($r['byCity'])],
            ['title' => __('hr_reports.training.by_month'), 'headings' => [$t('month'), $t('courses')], 'rows' => $r['byMonth']->map(fn (int $n, string $m): array => [$m, $n])->values()->all()],
            ['title' => __('hr_reports.training.untrained'), 'headings' => [$t('name'), $t('city')], 'rows' => $r['untrained']->map(fn (array $p): array => [$p['name'], $p['city']])->all()],
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
     * @return list<int>
     */
    #[Computed]
    public function years(): array
    {
        // toBase(): an empty Eloquent collection stays one after map(), and its unique() expects models.
        $years = Course::query()->whereNotNull('start_date')->get(['start_date'])->toBase()
            ->map(fn (Course $course): int => $course->start_date->year)
            ->push(now()->year)->unique()->sortDesc()->values();

        return array_values($years->all());
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function categories(): array
    {
        return CourseCategory::query()->orderBy('name_en')->get()->pluck('name', 'id')->all();
    }

    private function report(): TrainingReport
    {
        return new TrainingReport(
            Auth::user(),
            filled($this->year) ? (int) $this->year : null,
            filled($this->categoryId) ? (int) $this->categoryId : null,
        );
    }

    private function authorizeReport(): void
    {
        abort_unless(ReportCatalog::allows('training', Auth::user()), 403);
    }
}; ?>

@php
    $r = $this->r;
    $limit = TrainingReport::UNTRAINED_LIMIT;
@endphp

<x-desk.register class="@container" heading-id="hr-report-training-heading" :title="__('hr_reports.reports.training.title')" :count="$r['courses']">
    <x-slot:figures>{{ __('hr_reports.reports.training.lead') }} {{ __('hr_reports.training.no_start_date_note') }}</x-slot:figures>

    <x-slot:filters>
        <x-ui.header-filter-select id="training-year" :label="__('hr_reports.training.year')" wire:model.live="year" :value="(string) $year" data-testid="training-year">
            <option value="">{{ __('hr_reports.training.all_years') }}</option>
            @foreach ($this->years as $y)
                <option value="{{ $y }}">{{ $y }}</option>
            @endforeach
        </x-ui.header-filter-select>

        <x-ui.header-filter-select id="training-category" :label="__('hr_reports.training.category')" wire:model.live="categoryId" :value="(string) $categoryId">
            <option value="">{{ __('hr_reports.all') }}</option>
            @foreach ($this->categories as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </x-ui.header-filter-select>
    </x-slot:filters>

    <x-slot:actions>
        <x-ui.button tone="action" icon="arrow-down-tray" wire:click="export" data-test="export-report">{{ __('hr_reports.export') }}</x-ui.button>
    </x-slot:actions>

    <div class="grid gap-4" wire:loading.class="opacity-60">
        <div class="grid grid-cols-2 gap-4 @3xl:grid-cols-4">
            <x-ui.stat-tile :label="__('hr_reports.training.courses')" :value="number_format($r['courses'])" icon="document-text" data-testid="training-courses" />
            <x-ui.stat-tile :label="__('hr_reports.training.trained')" :value="number_format($r['trained'])" :denominator="number_format($r['people'])" icon="users" tone="good" data-testid="training-trained" />
            <x-ui.stat-tile :label="__('hr_reports.training.cost')" :value="number_format($r['cost'], 2)" icon="banknotes" data-testid="training-cost" />
            <x-ui.stat-tile :label="__('hr_reports.training.certificates')" :value="number_format($r['certificates'])" icon="check-circle" />
        </div>

        <p class="type-meta text-secondary-medium dark:text-ink-muted">{{ $r['coveragePercent'] === null ? '-' : __('hr_reports.training.coverage_of', ['n' => $r['coveragePercent'], 'm' => $r['people']]) }}</p>

        <div class="grid gap-4 @3xl:grid-cols-2">
            <x-ui.panel :title="__('hr_reports.training.by_category')"><div class="p-4"><x-hr::report.bars :rows="$r['byCategory']" /></div></x-ui.panel>
            <x-ui.panel :title="__('hr_reports.training.by_status')"><div class="p-4"><x-hr::report.bars :rows="$r['byStatus']" /></div></x-ui.panel>
            <x-ui.panel :title="__('hr_reports.training.by_city')"><div class="p-4"><x-hr::report.bars :rows="$r['byCity']" /></div></x-ui.panel>
            <x-ui.panel :title="__('hr_reports.training.providers')">
                <div class="px-4 py-2">
                    @forelse ($r['byOrganization'] as $name => $count)
                        <div class="flex items-baseline justify-between gap-2 border-b border-panel-border py-2 text-sm last:border-0">
                            <span class="text-secondary-dark dark:text-ink">{{ $name }}</span><span class="tabular-nums text-secondary-medium dark:text-ink-muted">{{ $count }}</span>
                        </div>
                    @empty
                        <p class="py-2 type-meta text-secondary-medium dark:text-ink-muted">{{ __('hr_reports.nothing') }}</p>
                    @endforelse
                </div>
            </x-ui.panel>
        </div>

        @if ($r['byMonth']->isNotEmpty())
            <x-ui.panel :title="__('hr_reports.training.by_month')">
                <div class="p-4"><x-hr::report.months :months="$r['byMonth']" /></div>
            </x-ui.panel>
        @endif

        <x-ui.panel :title="__('hr_reports.training.untrained')" :count="$r['untrainedCount']">
            <div class="px-4 py-2" data-testid="training-untrained-count">
                @foreach ($r['untrained']->take($limit) as $p)
                    <div class="flex items-baseline justify-between gap-2 border-b border-panel-border py-2 text-sm last:border-0" wire:key="untrained-{{ $loop->index }}">
                        @if (filled($p['username']))
                            <a href="{{ route('hr.employees.show', $p['username']) }}" class="text-secondary-dark underline dark:text-ink" wire:navigate>{{ $p['name'] }}</a>
                        @else
                            <span class="text-secondary-dark dark:text-ink">{{ $p['name'] }}</span>
                        @endif
                        <span class="text-secondary-medium dark:text-ink-muted">{{ $p['city'] }}</span>
                    </div>
                @endforeach
                @if ($r['untrainedCount'] > $limit)
                    <p class="py-2 type-meta text-secondary-medium dark:text-ink-muted">{{ __('hr_reports.showing', ['a' => $limit, 'b' => $r['untrainedCount']]) }}</p>
                @endif
            </div>
        </x-ui.panel>
    </div>
</x-desk.register>
