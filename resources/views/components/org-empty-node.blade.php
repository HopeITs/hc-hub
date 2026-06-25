@props(['position', 'compact' => false])

@php
    $org = orgCardTokens($compact);
    $center = $position->center?->name;
    $city = $position->city?->name;
@endphp

<div {{ $attributes->merge(['class' => 'org-id-card org-id-card--vacant '.$org['modifier']]) }}>
    <div class="org-id-card__photo">
        <div class="org-id-card__bar org-id-card__bar--vacant" aria-hidden="true"></div>
        <span class="org-id-card__avatar-placeholder" aria-hidden="true">
            <x-icons.user class="size-[42%]" />
        </span>
    </div>

    <div class="org-id-card__info">
        <div class="org-id-card__name">
            <span>@lang('general.vacant-position')</span>
        </div>

        <div class="org-id-card__position">{{ $position->title }}</div>

        @if ($center)
            <div class="org-id-card__center">{{ $center }}</div>
        @endif

        @if ($city)
            <div class="org-id-card__city">{{ $city }}</div>
        @endif

        <div class="org-id-card__divider" aria-hidden="true"></div>
    </div>
</div>
