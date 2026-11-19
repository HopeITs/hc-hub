@props(['employee', 'compact' => false, 'empty' => null, 'link' => false])

{{-- An HR employee shown by name in the interface language, with the other language beside it
     and, unless compact, their current position · job level · department · center from HR.
     empty: what to say when there is nobody (default "Not assigned").
     link: the name opens the person's profile page (never inside another link or button). --}}
@if ($employee === null)
    <div {{ $attributes->class('flex min-w-0 items-center gap-2.5') }}>
        <p class="truncate text-sm text-secondary-medium dark:text-ink-muted">{{ $empty ?? __('common.not_assigned') }}</p>
    </div>
@else
    @php
        $positionSummary = null;

        if (! $compact) {
            try {
                $positionSummary = \Modules\Hr\Models\Employee::positionSummary($employee->id) ?? __('common.no_active_position');
            } catch (\Illuminate\Database\QueryException) {
                $positionSummary = '#'.$employee->id.' · '.__('common.hr_unavailable');
            }
        }

        $profileUrl = $link ? \Modules\Hr\Models\Employee::profileUrl($employee->id) : null;
    @endphp
    <div {{ $attributes->class('flex min-w-0 items-center gap-2.5') }}>
        <x-ui.avatar :src="\Modules\Hr\Models\Employee::avatarUrl($employee->id)" @class(['shrink-0 rounded-full', 'size-6' => $compact, 'size-8' => ! $compact]) aria-hidden="true" />
        <div class="min-w-0">
            @if ($profileUrl === null)
                <p class="truncate text-sm font-medium text-secondary-dark dark:text-ink">{{ $employee->name }}</p>
            @else
                <a href="{{ $profileUrl }}" wire:navigate class="block truncate text-sm font-medium text-secondary-dark hover:text-primary hover:underline dark:text-ink dark:hover:text-brand-text" data-employee-link="{{ $employee->id }}">{{ $employee->name }}</a>
            @endif
            @unless ($compact)
                <p class="truncate text-xs text-secondary-medium dark:text-ink-muted" dir="auto" lang="{{ \App\Enums\Locale::current()->other()->value }}">{{ $employee->secondary_name }}</p>
                <p class="truncate text-xs text-secondary-medium dark:text-ink-muted" data-employee-position>{{ $positionSummary }}</p>
            @endunless
        </div>
    </div>
@endif
