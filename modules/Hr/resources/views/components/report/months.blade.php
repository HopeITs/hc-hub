@props(['months'])

{{-- A column per month: $months maps "YYYY-MM" to a number. --}}
@php
    $peak = max(0.0001, (float) $months->max());
@endphp

<div class="flex h-32 items-end gap-1" dir="ltr">
    @foreach ($months as $month => $value)
        <div class="flex h-full flex-1 items-end" title="{{ $month }}: {{ $value }}">
            <div class="w-full rounded-t bg-secondary-dark dark:bg-ink-muted" style="height: {{ $value / $peak * 100 }}%"></div>
        </div>
    @endforeach
</div>
<div class="mt-1 flex justify-between type-meta tabular-nums text-secondary-medium dark:text-ink-muted" dir="ltr">
    <span>{{ $months->keys()->first() }}</span><span>{{ $months->keys()->last() }}</span>
</div>
