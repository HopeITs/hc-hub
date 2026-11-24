<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Support\Reports\ReportCatalog;

/**
 * The reports hub: one card per report the viewer may open. Hiding a card is
 * not the gate; each report page checks ReportCatalog::allows() itself.
 */
new #[Layout('layouts::desk'), Title('hr_reports.title')] class extends Component {
    public function mount(): void
    {
        abort_if($this->reports === [], 403);
    }

    /**
     * @return array<string, array{route: string, title: string, lead: string}>
     */
    #[Computed]
    public function reports(): array
    {
        $reports = [];

        foreach (ReportCatalog::visibleTo(Auth::user()) as $key => $report) {
            $reports[$key] = [
                'route' => $report['route'],
                'title' => ReportCatalog::title($key),
                'lead' => ReportCatalog::lead($key),
            ];
        }

        return $reports;
    }
}; ?>

<x-desk.register heading-id="hr-reports-heading" :title="__('hr_reports.title')" :count="count($this->reports)">
    <x-slot:figures>{{ __('hr_reports.lead') }}</x-slot:figures>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-testid="reports-hub">
        @foreach ($this->reports as $key => $report)
            <a href="{{ route($report['route']) }}" wire:navigate wire:key="report-card-{{ $key }}" data-testid="report-card-{{ $key }}" class="block rounded-panel border border-panel-border bg-white p-5 shadow-card transition-[translate,box-shadow] hover:-translate-y-0.5 hover:shadow-card-hover motion-reduce:transition-none motion-reduce:hover:translate-y-0 dark:border-transparent dark:bg-surface-1 dark:shadow-raised">
                <h2 class="text-base font-bold text-secondary-dark dark:text-ink">{{ $report['title'] }}</h2>
                <p class="mt-2 type-meta text-secondary-medium dark:text-ink-muted">{{ $report['lead'] }}</p>
                <span class="mt-4 inline-block text-sm font-semibold text-secondary-dark underline dark:text-ink">{{ __('hr_reports.open') }}</span>
            </a>
        @endforeach
    </div>
</x-desk.register>
