@props(['project' => null, 'inactive' => null])

{{-- «Center inactive»: the project's own center is one HR has deactivated
     (D-66, W-02). Text with an icon, never colour alone. Pass the project
     (read from the in-memory HR lookup, so a list costs no query per row) or
     an already resolved `inactive` flag for rows that are plain arrays.
     Renders nothing for an active center, a hosted project or no center. --}}
@php
    $isInactive = $inactive ?? ($project?->hasInactiveCenter() ?? false);
@endphp

@if ($isInactive)
    <span {{ $attributes->class('inline-flex items-center gap-1 rounded-full border border-status-warn bg-status-warn-soft px-2 py-0.5 text-[11px] font-bold whitespace-nowrap text-secondary-dark dark:text-ink') }} data-test="center-inactive-badge">
        <flux:icon name="pause-circle" variant="micro" class="size-3 shrink-0" aria-hidden="true" />
        {{ __('projects.center_inactive_badge') }}
    </span>
@endif
