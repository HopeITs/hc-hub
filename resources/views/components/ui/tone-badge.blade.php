@props(['tone' => 'neutral', 'icon' => null])

{{-- A small tinted badge for a word that is not an enum case (a count, a state
     a page computes, a tag). The tone is a StatusTone or its value
     (good / warn / critical / active / neutral), so the colours come from the
     same status tokens as x-ui.enum-badge and read correctly in dark. --}}
@php
    $badgeTone = $tone instanceof \App\Enums\StatusTone ? $tone : (\App\Enums\StatusTone::tryFrom((string) $tone) ?? \App\Enums\StatusTone::Neutral);
@endphp

<span {{ $attributes->class('inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs font-semibold whitespace-nowrap '.$badgeTone->badgeClasses()) }} data-tone="{{ $badgeTone->value }}">
    @if ($icon !== null)
        <flux:icon :icon="$icon" variant="micro" class="size-3.5 shrink-0" />
    @endif
    {{ $slot }}
</span>
