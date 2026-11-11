{{-- The same statements as the list, as a program-by-level grid; each cell shows its worst status. Shown when the page's view is chart. --}}
@php
    $grid = $this->statusGrid;

    $statusKeys = ['ontrack', 'waiting', 'behind', 'none', 'notarget'];
    $cellClasses = [
        'ontrack' => 'bg-status-good-soft text-status-good',
        'waiting' => 'bg-status-warn-soft text-status-warn',
        'behind' => 'bg-status-critical-soft text-primary dark:text-brand-text',
        'none' => 'bg-secondary-soft text-secondary-dark dark:bg-surface-2 dark:text-ink',
        'notarget' => 'bg-secondary-soft text-secondary-dark dark:bg-surface-2 dark:text-ink',
    ];
    $statusIcons = ['ontrack' => 'check-circle', 'waiting' => 'clock', 'behind' => 'arrow-trending-down', 'none' => 'exclamation-triangle', 'notarget' => 'minus-circle'];
@endphp

<div class="space-y-3" data-test="results-chart">
    <ul class="flex flex-wrap items-center gap-x-4 gap-y-1" aria-label="{{ __('results_framework.chart_legend') }}">
        @foreach ($statusKeys as $key)
            @continue ($grid['totals'][$key] === 0)
            <li class="inline-flex items-center gap-1.5 type-meta text-secondary-dark dark:text-ink">
                <flux:icon :name="$statusIcons[$key]" variant="micro" class="size-3.5" aria-hidden="true" />
                {{ __('results_framework.status_'.$key) }}
                · <span class="tabular-nums">{{ $grid['totals'][$key] }}</span>
            </li>
        @endforeach
    </ul>

    <x-ui.panel :title="__('results_framework.statements_by_program_and_level')" :subtitle="__('results_framework.each_cell_shows_its_worst_status')">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[34rem] border-separate border-spacing-2 p-2 text-start">
                <thead>
                    <tr>
                        <th scope="col" class="p-2 text-start type-label text-secondary-medium dark:text-ink-muted">{{ __('common.program') }}</th>
                        @foreach ($grid['levels'] as $level)
                            <th scope="col" class="p-2 text-start type-label text-secondary-medium dark:text-ink-muted">{{ $level['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grid['rows'] as $gridRow)
                        <tr wire:key="results-grid-{{ $loop->index }}">
                            <th scope="row" class="p-2 text-start align-middle text-sm font-semibold text-secondary-dark dark:text-ink"><bdi>{{ $gridRow['label'] }}</bdi></th>
                            @foreach ($grid['levels'] as $level)
                                @php($cell = $gridRow['cells'][$level['value']] ?? null)
                                <td class="align-top">
                                    @if ($cell === null)
                                        <span class="block p-3 text-secondary-light" aria-label="{{ __('results_framework.no_statements_here') }}">—</span>
                                    @else
                                        <div class="rounded-xl p-3 {{ $cellClasses[$cell['worst']] }}" data-test="grid-cell" data-status="{{ $cell['worst'] }}">
                                            <p class="flex items-center gap-1.5 text-sm font-bold">
                                                <flux:icon :name="$statusIcons[$cell['worst']]" variant="micro" class="size-4 shrink-0" aria-hidden="true" />
                                                {{ __('results_framework.status_'.$cell['worst']) }}
                                            </p>
                                            <p class="mt-1 type-meta">
                                                {{ $cell['total'] === 1
                                                    ? __('results_framework.one_statement')
                                                    : ($cell['needs'] === 0
                                                    ? __('results_framework.cell_all_fine', ['total' => $cell['total']])
                                                    : __('results_framework.cell_need_a_hand', ['count' => $cell['needs'], 'total' => $cell['total']])) }}
                                            </p>
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.panel>
</div>
