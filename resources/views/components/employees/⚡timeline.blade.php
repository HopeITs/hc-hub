<?php

use App\Support\Timeline\EmployeeTimeline;
use App\Support\Timeline\EmployeeTimelineTab;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * An employee's timeline (roadmap 4.7, D6): contracts, project memberships,
 * equipment and journeys, read from the lasting records through each
 * module's TimelineSource, so it stands even after the activity log is
 * pruned. Drawn as the "Timeline" tab of the profile through the registry.
 */
new class extends Component {
    #[Locked]
    public int $employeeId = 0;

    public function mount(int $employeeId): void
    {
        $this->employeeId = $employeeId;

        abort_unless(Auth::user() !== null && app(EmployeeTimelineTab::class)->visibleTo(Auth::user(), $employeeId), 403);
    }

    /** @return list<array{source: string, label: string, icon: string, date: \Carbon\CarbonImmutable|null, title: string, detail: string|null, href: string|null}> */
    #[Computed]
    public function entries(): array
    {
        return app(EmployeeTimeline::class)->entries(Auth::user(), $this->employeeId);
    }
};
?>

@php
    $entries = $this->entries;
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<div data-test="employee-timeline">
    <x-ui.panel :title="__('timeline.title')" :subtitle="__('timeline.subtitle')" :count="count($entries)">
        @if ($entries === [])
            <p class="p-4 text-sm {{ $muted }}" data-test="employee-timeline-empty">{{ __('timeline.empty') }}</p>
        @else
            <ol class="divide-y divide-panel-border px-4">
                @foreach ($entries as $index => $entry)
                    <li class="flex flex-wrap items-start gap-3 py-3" wire:key="timeline-{{ $index }}" data-timeline-source="{{ $entry['source'] }}">
                        <flux:icon :name="$entry['icon']" variant="micro" class="mt-0.5 size-4 shrink-0 {{ $muted }}" />

                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-secondary-dark dark:text-ink">{{ $entry['title'] }}</p>
                            @if ($entry['detail'] !== null)
                                @if ($entry['href'] !== null)
                                    <a href="{{ $entry['href'] }}" wire:navigate class="text-sm {{ $muted }} hover:underline">{{ $entry['detail'] }}</a>
                                @else
                                    <p class="text-sm {{ $muted }}">{{ $entry['detail'] }}</p>
                                @endif
                            @endif
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-2 text-xs {{ $muted }}">
                            <span class="rounded-md bg-secondary-soft px-1.5 py-0.5 font-semibold text-secondary-dark dark:bg-surface-2 dark:text-ink">{{ $entry['label'] }}</span>
                            <span data-timeline-date>{{ $entry['date'] === null ? __('timeline.undated') : $entry['date']->translatedFormat(__('formats.date')) }}</span>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </x-ui.panel>
</div>
