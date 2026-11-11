{{--
    One position in the team chart and, folded beneath it, its direct reports.
    Reads $chart, $positions, $nodeId and $depth; includes itself per child.
--}}
@php
    $node = $positions->get($nodeId);
    $kids = array_values(array_filter($chart['children'][$nodeId] ?? [], fn (int $id): bool => $positions->has($id)));
    $people = $chart['holders'][$nodeId] ?? [];
    $open = max(0, (int) $node?->max_emp - (int) $node?->exist_emp);
@endphp

@if ($node !== null && $depth < 25)
    <li role="treeitem" wire:key="team-{{ $nodeId }}" data-team-position="{{ $nodeId }}">
        <details @if ($depth < 1) open @endif class="group">
            <summary class="flex cursor-pointer list-none flex-wrap items-center gap-x-3 gap-y-1 rounded-lg border border-panel-border bg-white px-3 py-2 dark:bg-surface-1">
                @if ($kids !== [])
                    <flux:icon.chevron-right class="size-4 shrink-0 transition group-open:rotate-90 rtl:rotate-180 rtl:group-open:rotate-90" />
                @else
                    <span class="size-4 shrink-0"></span>
                @endif
                <span class="font-semibold" dir="auto">{{ $node->title }}</span>
                <span class="text-xs text-secondary-medium dark:text-ink-muted">{{ \Modules\Core\Models\Program::nameOf($node->program_id) }}</span>
                <span class="flex flex-wrap gap-1">
                    @foreach ($people as $person)
                        @if ($person['username'])
                            <a href="{{ route('hr.employees.show', $person['username']) }}" wire:navigate class="rounded-md bg-secondary-soft px-1.5 py-0.5 text-xs font-semibold dark:bg-surface-2" dir="auto">{{ $person['name'] }}</a>
                        @else
                            <span class="rounded-md bg-secondary-soft px-1.5 py-0.5 text-xs font-semibold dark:bg-surface-2" dir="auto">{{ $person['name'] }}</span>
                        @endif
                    @endforeach
                    @if ($open > 0)
                        <span class="rounded-md bg-status-warn-soft px-1.5 py-0.5 text-xs font-semibold text-status-warn" data-open-seats>{{ trans_choice('hr_positions.team.open_count', $open, ['count' => $open]) }}</span>
                    @endif
                </span>
                @if ($kids !== [])
                    <span class="ms-auto text-xs text-secondary-medium dark:text-ink-muted">{{ trans_choice('hr_positions.team.reports', count($kids), ['count' => count($kids)]) }}</span>
                @endif
            </summary>
            @if ($kids !== [])
                <ul class="ms-4 mt-2 flex flex-col gap-2 border-s border-panel-border ps-4" role="group">
                    @foreach ($kids as $childId)
                        @include('hr::pages.positions._team-node', ['nodeId' => $childId, 'depth' => $depth + 1])
                    @endforeach
                </ul>
            @endif
        </details>
    </li>
@endif
