@props(['rows', 'empty' => null])

{{-- A list of horizontal bars: each row is ['label', 'count', 'percent'] (percent drives the bar). --}}
@forelse ($rows as $row)
    <div class="mb-3 last:mb-0">
        <div class="flex items-baseline justify-between gap-2 text-sm">
            <span class="min-w-0 break-words text-secondary-dark dark:text-ink">{{ $row['label'] }}</span>
            <span class="shrink-0 tabular-nums text-secondary-medium dark:text-ink-muted">{{ $row['count'] }}@isset($row['percent']) &middot; {{ $row['percent'] }}%@endisset</span>
        </div>
        <div class="mt-1 h-2 rounded-full bg-secondary-soft dark:bg-surface-2"><div class="h-2 rounded-full bg-secondary-dark dark:bg-ink-muted" style="width: {{ min(100, max(0, $row['percent'] ?? 0)) }}%"></div></div>
    </div>
@empty
    <p class="type-meta text-secondary-medium dark:text-ink-muted">{{ $empty ?? __('hr_reports.nothing') }}</p>
@endforelse
