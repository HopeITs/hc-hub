@props(['case' => null, 'label' => null, 'tone' => null])

{{-- One badge per StatusTone (W-28). A status enum declares its tone
     through HasStatusTone; enums that are not statuses (ResultType, DonorType,
     Period, IndicatorMeasure, ...) fall back to the neutral tone.

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
@else
    <span {{ $attributes->class('inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs font-semibold whitespace-nowrap '.$badgeTone->badgeClasses()) }} data-tone="{{ $badgeTone->value }}">
        @if ($badgeIcon !== null)
            <flux:icon :name="$badgeIcon" variant="micro" class="size-3 shrink-0" />
        @endif
        {{ $label ?? $case->label() }}
    </span>
@endif
