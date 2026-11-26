@props(['tone' => 'neutral', 'icon' => null])

{{-- The one tag (plan task 2.4): a 13px word that is not a status, such as a
     type, a group, a count or a category enum (ResultType, DonorType ...).
     The icon is optional. A status is an x-ui.chip instead.

     Tones are the chip's (StatusTone, or its value as a string; anything else
     reads neutral), brand palette only:
       good      solid ink fill, white text
       warn      soft grey fill
       critical  brand red fill, white text
       active    ink outline on a faint fill
       neutral   dashed grey outline (the default)

     The old names render through this tag: x-ui.enum-badge for a non-status
     enum, x-ui.tone-badge without an icon, and the quiet
     FluxMarkup::statusPill(). The Desk .dk-tag classes alias it in desk.css. --}}
@php
    $tagTone = $tone instanceof \App\Enums\StatusTone ? $tone : (\App\Enums\StatusTone::tryFrom((string) $tone) ?? \App\Enums\StatusTone::Neutral);
@endphp

<span {{ $attributes->class('ui-tag '.$tagTone->badgeClasses()) }} data-tone="{{ $tagTone->value }}">
    @if ($icon !== null)
        <flux:icon :icon="$icon" variant="micro" class="size-3 shrink-0" aria-hidden="true" />
    @endif
    {{ $slot }}
</span>
