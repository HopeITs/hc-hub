{{--
    The «Pending» mark of a donor the General Manager has not approved yet
    (W-04, D-57). Text and icon, never colour alone.
--}}
@props(['donor'])

@if ($donor->isPending())
    <span {{ $attributes->class('inline-flex items-center gap-1 rounded-full border border-status-warn bg-status-warn-soft px-2 py-0.5 text-xs font-semibold text-secondary-dark dark:text-ink') }} data-donor-pending-badge>
        <flux:icon.clock variant="micro" class="size-3" aria-hidden="true" />
        {{ __('donors.pending_badge') }}
    </span>
@endif
