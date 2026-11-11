@props(['delivered' => null, 'returned' => null])

{{--
    Where one checklist row stands, as a word in a tinted chip (never a colour
    alone): received back, waiting to be taken back, delivered (the employee
    holds it), or still waiting to be handed over.
--}}
@php
    [$key, $classes] = match (true) {
        $returned === 'received' => ['received', 'bg-status-good-soft text-status-good'],
        $returned === 'pending' => ['return_pending', 'bg-status-warn-soft text-status-warn'],
        $delivered === 'delivered' => ['delivered', 'bg-status-active-soft text-status-active'],
        default => ['on_hold', 'bg-secondary-soft text-secondary-dark dark:bg-surface-2 dark:text-ink'],
    };
@endphp

<span {{ $attributes->class('inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold whitespace-nowrap '.$classes) }} data-checklist-status="{{ $key }}">
    {{ __('hr_checklists.status.'.$key) }}
</span>
