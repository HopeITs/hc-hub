@props(['tone' => 'neutral', 'icon' => null])

{{-- The one status chip (plan task 2.4): a pill, 14px, an icon and its label.
     A status never rests on colour alone (D1), so the chip always draws an
     icon: the one passed in, else its tone's own (StatusTone::icon()).

     Tones (StatusTone, or its value as a string; anything else reads neutral),
     brand palette only, told apart by shape as much as by colour:
       good      finished or accepted     solid ink fill, white text
       warn      needs attention          soft grey fill
       critical  failed, refused, late    brand red fill, white text
       active    in flight                ink outline on a faint fill
       neutral   not started, out of play dashed grey outline

     The old names render through this chip: x-ui.enum-badge (status enums),
     x-ui.hold-chip, x-ui.blocked-badge, x-ui.assignment-badge,
     x-ui.project-funding-badge, x-ui.tone-badge with an icon, and
     FluxMarkup::statusPill() (x-ui.status-pill). The Desk .dk-pill classes
     alias the same shape and tones in desk.css. A word that is not a status
     is an x-ui.tag. --}}
@php
    $chipTone = $tone instanceof \App\Enums\StatusTone ? $tone : (\App\Enums\StatusTone::tryFrom((string) $tone) ?? \App\Enums\StatusTone::Neutral);
@endphp

<span {{ $attributes->class('ui-chip '.$chipTone->badgeClasses()) }} data-tone="{{ $chipTone->value }}">
    <flux:icon :icon="$icon ?? $chipTone->icon()" variant="micro" class="size-3.5 shrink-0" aria-hidden="true" />
    {{ $slot }}
</span>
