@props(['position', 'employee' => null, 'isManager' => false, 'managerType' => 'direct', 'compact' => false])

@php
    $org = orgCardTokens($compact);

    $employee ??= $position->activeContracts->first()?->employee;
    $center = $position->center?->name;
    $city = $position->city?->slug ?? $position->center?->city?->slug;
    $cityName = $position->city?->name ?? $position->center?->city?->name;

    $crossRelation = $managerType === 'direct' ? 'techManager' : 'directManager';
    $crossLabel = $managerType === 'direct' ? __('general.tech-manager') : __('general.direct-manager');
    $crossEmployee = $position->{$crossRelation}?->activeContracts?->first()?->employee;

    $modifiers = collect([
        'org-id-card',
        $org['modifier'],
        $isManager ? 'org-id-card--manager' : null,
        $employee->is_board_member ? 'org-id-card--board' : null,
    ])
        ->filter()
        ->implode(' ');
@endphp

<button type="button" wire:click="$dispatch('open-profile', { employeeId: {{ $employee->id }} })"
    aria-label="{{ __('general.view-profile-of', ['name' => $employee->name]) }}"
    {{ $attributes->merge(['class' => $modifiers]) }}>
    <div class="org-id-card__header">
        <div class="org-id-card__badge" @if ($cityName) title="{{ $cityName }}" @endif>{{ $city }}</div>
        <div class="org-id-card__badge">{{ $position->program?->slug }}</div>
    </div>

    <div class="org-id-card__photo">
        <div class="org-id-card__bar" aria-hidden="true"></div>
        <img class="org-id-card__avatar" src="{{ $employee->getFirstMediaUrl('avatar') }}" alt="" loading="lazy">
    </div>

    <div class="org-id-card__info">
        <div class="org-id-card__name">{{ $employee->first_name.' '.$employee->last_name }}</div>
        <div class="org-id-card__position">{{ $position->title }}</div>
    </div>
</button>
