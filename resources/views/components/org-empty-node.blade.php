@props(['position', 'compact' => false])

@php
    $org = orgCardTokens($compact);
    $city = $position->city?->slug ?? $position->center?->city?->slug;
    $cityName = $position->city?->name ?? $position->center?->city?->name;
@endphp

<div {{ $attributes->merge(['class' => 'org-id-card org-id-card--vacant '.$org['modifier']]) }}>
    <div class="org-id-card__header">
        <div class="org-id-card__badge" @if ($cityName) title="{{ $cityName }}" @endif>{{ $city }}</div>
        <div class="org-id-card__badge">{{ $position->program?->slug }}</div>
    </div>

    <div class="org-id-card__photo">
        <div class="org-id-card__bar org-id-card__bar--vacant" aria-hidden="true"></div>
        <img class="org-id-card__avatar"
            src="{{ asset('images/default-avatar.png') }}"
            alt=""
            loading="lazy"
            aria-hidden="true">
    </div>

    <div class="org-id-card__info">
        <div class="org-id-card__name">@lang('general.vacant-position')</div>
        <div class="org-id-card__position">{{ $position->title }}</div>
    </div>
</div>
