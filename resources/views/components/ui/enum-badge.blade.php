@props(['case' => null, 'label' => null, 'tone' => null])

{{-- One tinted badge per StatusTone (W-28). A status enum declares its tone
     through HasStatusTone; enums that are not statuses (ResultType, DonorType,
     Period, IndicatorMeasure, ...) fall back to the neutral tone.

     `label` and `tone` override the case's own for a status that a flag
     qualifies: a proposal on hold reads "Submitted (On hold)" through
     Proposal::statusLabel() in the warn tone (W-06, D-58, contracts.md §1),
     while the stored status stays Submitted. --}}
@php
    $badgeTone = $tone
        ?? ($case instanceof \App\Enums\Concerns\HasStatusTone ? $case->tone() : \App\Enums\StatusTone::Neutral);
@endphp

@if ($case === null)
    <span {{ $attributes->class('text-secondary-light') }} aria-label="{{ __('ui.not_set') }}">—</span>
@else
    <span {{ $attributes->class('inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-semibold whitespace-nowrap '.$badgeTone->badgeClasses()) }} data-tone="{{ $badgeTone->value }}">
        {{ $label ?? $case->label() }}
    </span>
@endif
