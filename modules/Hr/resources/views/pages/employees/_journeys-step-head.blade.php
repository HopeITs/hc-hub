{{--
    The first lines of one journey step: number, name, state (a word in a
    tinted chip, never a colour alone), Late, and who acts / due / after.
    Expects $index, $step, $label, $afterLabel (or null) and optionally
    $dueLabel. Included by the _journeys-* partials.
--}}
@php
    $stateClasses = match ($step['state']) {
        'done' => 'bg-status-good-soft text-status-good',
        'todo' => 'bg-status-warn-soft text-status-warn',
        'problem' => 'bg-status-critical-soft text-status-critical',
        default => 'bg-secondary-soft text-secondary-dark dark:bg-surface-2 dark:text-ink',
    };
@endphp

<p class="flex flex-wrap items-center gap-2 font-semibold {{ $ink }}">
    <span class="tabular-nums {{ $muted }}" aria-hidden="true">{{ $index + 1 }}.</span>
    <span>{{ $label }}</span>
    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold whitespace-nowrap {{ $stateClasses }}" data-journey-state="{{ $step['state'] }}">{{ __('hr_journeys.states.'.$step['state']) }}</span>
    @if ($step['data']['optional'] ?? false)
        <span class="inline-flex items-center rounded-md bg-secondary-soft px-2 py-0.5 text-xs font-semibold text-secondary-dark dark:bg-surface-2 dark:text-ink">{{ __('hr_journeys.optional') }}</span>
    @endif
    @if ($step['late'])
        <span class="inline-flex items-center rounded-md bg-status-critical-soft px-2 py-0.5 text-xs font-semibold text-status-critical">{{ __('hr_journeys.late') }}</span>
    @endif
</p>

<dl class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs {{ $muted }}">
    <div class="flex gap-1">
        <dt>{{ __('hr_journeys.owner') }}:</dt>
        <dd>{{ __('hr_journeys.owners.'.$step['owner']) }}</dd>
    </div>
    @if ($step['due'])
        <div class="flex gap-1">
            <dt>{{ $dueLabel ?? __('hr_journeys.due') }}:</dt>
            <dd class="tabular-nums">{{ $date($step['due']) }}</dd>
        </div>
    @endif
    @if ($step['blocked_by'] && $afterLabel !== null)
        <div class="flex gap-1">
            <dt>{{ __('hr_journeys.after') }}:</dt>
            <dd>{{ $afterLabel }}</dd>
        </div>
    @endif
</dl>
