@props(['case' => null, 'label' => null, 'tone' => null])

{{-- An enum case as a chip or a tag (W-28, plan task 2.4). A status enum
     declares its tone through HasStatusTone and renders as x-ui.chip; enums
     that are not statuses (ResultType, DonorType, Period, IndicatorMeasure, ...)
     render as a neutral x-ui.tag.

     A status always shows an icon and its label (brand palette, D1): the tones
     are red plus greys, so the icon shape is what tells two greys apart. The
     icon is the case's own; a `tone` override (a qualified status) takes that
     tone's icon. Non-status enums carry no icon.

     `label` and `tone` override the case's own for a status that a flag
     qualifies: a proposal on hold reads "Submitted (On hold)" through
     Proposal::statusLabel() in the warn tone (W-06, D-58, contracts.md §1),
     while the stored status stays Submitted. --}}
@php
    $isStatus = $case instanceof \App\Enums\Concerns\HasStatusTone;
    $badgeTone = $tone ?? ($isStatus ? $case->tone() : \App\Enums\StatusTone::Neutral);
    $badgeIcon = match (true) {
        $tone !== null => $badgeTone->icon(),
        $isStatus => $case->icon(),
        default => null,
    };
@endphp

@if ($case === null)
    <span {{ $attributes->class('text-secondary-medium dark:text-ink-faint') }} aria-label="{{ __('ui.not_set') }}">—</span>
@elseif ($badgeIcon !== null)
    <x-ui.chip :tone="$badgeTone" :icon="$badgeIcon" :attributes="$attributes">{{ $label ?? $case->label() }}</x-ui.chip>
@else
    <x-ui.tag :tone="$badgeTone" :attributes="$attributes">{{ $label ?? $case->label() }}</x-ui.tag>
@endif
