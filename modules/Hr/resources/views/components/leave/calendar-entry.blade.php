@props(['entry', 'compact' => false])

{{--
    One absence on the team calendar (a TeamCoverage projection, never a
    model). Accepted: solid border and a tick; pending: dashed border and a
    clock; part of the day: a half circle. A colleague-level entry has no
    status and reads as accepted.
--}}
@php
    $pending = $entry['status'] === 'pending';
    $coverGap = $entry['cover_gap_dates'] ?? [];
    $label = ($entry['is_self'] ? __('hr_leave.calendar.you') : $entry['name']).' · '.$entry['type'];
    $details = collect([
        $entry['position'] !== '' ? $entry['position'] : null,
        $entry['partial'] ? __('hr_leave.calendar.partial').($entry['hours'] !== null ? ' '.$entry['hours'] : '') : null,
        $entry['status'] !== null ? __('hr_leave.calendar.status.'.$entry['status']) : null,
        $entry['substitute'] !== null ? __('hr_leave.calendar.substitute', ['name' => $entry['substitute']]) : null,
        // Who covers: nobody named (manager level only: a colleague entry has no status),
        // the cover away on some of the same days, or the viewer is the cover.
        $entry['substitute'] === null && $entry['status'] !== null ? __('hr_leave.calendar.no_cover') : null,
        $coverGap !== [] ? __('hr_leave.calendar.cover_also_away', ['dates' => collect($coverGap)->map(fn (string $date): string => \Carbon\CarbonImmutable::parse($date)->format('d-m'))->implode(__('hr_leave.calendar.date_separator'))]) : null,
        ($entry['covers_you'] ?? false) ? __('hr_leave.calendar.you_cover') : null,
    ])->filter()->implode(' · ');
@endphp

<div
    {{ $attributes->class([
        'flex min-w-0 items-center gap-1 rounded-md border px-1.5 py-0.5 text-xs leading-5',
        'border-solid border-status-good/50 bg-status-good-soft text-secondary-dark dark:bg-surface-2 dark:text-ink' => ! $pending,
        'border-dashed border-status-warn bg-white text-secondary-dark dark:bg-surface-1 dark:text-ink' => $pending,
        'ring-1 ring-primary/40' => $entry['is_self'],
    ]) }}
    title="{{ $label }}{{ $details !== '' ? ' — '.$details : '' }}"
    data-calendar-entry="{{ $entry['key'] }}"
    data-entry-status="{{ $entry['status'] ?? 'accepted' }}"
    @if ($coverGap !== []) data-cover-gap="{{ implode(',', $coverGap) }}" @endif
    @if ($entry['covers_you'] ?? false) data-you-cover @endif
>
    @if ($coverGap !== [])
        <flux:icon.exclamation-triangle variant="micro" class="size-3 shrink-0 text-status-warn" aria-hidden="true" />
    @endif
    @if ($pending)
        <flux:icon.clock variant="micro" class="size-3 shrink-0 text-status-warn" aria-hidden="true" />
    @else
        <flux:icon.check variant="micro" class="size-3 shrink-0 text-status-good" aria-hidden="true" />
    @endif
    @if ($entry['partial'])
        <span class="inline-block size-2.5 shrink-0 rounded-full bg-linear-to-r from-current from-50% to-transparent to-50% ring-1 ring-current" aria-label="{{ __('hr_leave.calendar.partial') }}"></span>
    @endif
    <span class="truncate" dir="auto">{{ $label }}</span>
    @if (! $compact && $details !== '')
        <span class="hidden truncate text-secondary-medium sm:inline dark:text-ink-muted" dir="auto">— {{ $details }}</span>
    @endif
</div>
