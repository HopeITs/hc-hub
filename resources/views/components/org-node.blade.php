@props([
    'position',
    'employee' => null,
    'isManager' => false,
    'managerType' => 'direct',
    'compact' => false,
])

@php
    $org = orgCardTokens($compact);

    $employee ??= $position->activeContracts->first()?->employee;
    $center = $position->center?->name;
    $city = $position->city?->name;

    $crossRelation = $managerType === 'direct' ? 'techManager' : 'directManager';
    $crossLabel = $managerType === 'direct' ? __('general.tech-manager') : __('general.direct-manager');
    $crossEmployee = $position->{$crossRelation}?->activeContracts?->first()?->employee;

    $modifiers = collect([
        'org-id-card',
        $org['modifier'],
        $isManager ? 'org-id-card--manager' : null,
        $employee->is_board_member ? 'org-id-card--board' : null,
    ])->filter()->implode(' ');
@endphp

<button type="button"
    wire:click="$dispatch('open-profile', { employeeId: {{ $employee->id }} })"
    aria-label="{{ __('general.view-profile-of', ['name' => $employee->name]) }}"
    {{ $attributes->merge(['class' => $modifiers]) }}>
    <div class="org-id-card__photo">
        <div class="org-id-card__bar" aria-hidden="true"></div>
        <img class="org-id-card__avatar"
            src="{{ $employee->getFirstMediaUrl('avatar') }}"
            alt=""
            loading="lazy">
    </div>

    <div class="org-id-card__info">
        <div class="org-id-card__name">
            <span>{{ $employee->first_name  .' '.$employee->last_name }}</span>
            {{-- <span>{{  }}</span> --}}
        </div>

        <div class="org-id-card__position">{{ $position->title }}</div>

        @if ($center)
            <div class="org-id-card__center">{{ $center }}</div>
        @endif

        @if ($city)
            <div class="org-id-card__city">{{ $city }}</div>
        @endif

        @if ($crossEmployee)
            <div class="org-id-card__matrix" title="{{ $crossLabel }}: {{ $crossEmployee->name }}">
                <svg class="size-[3.2cqw] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="3 2" aria-hidden="true">
                    <path stroke-linecap="round" d="M4 12h16" />
                </svg>
                <span>{{ $crossEmployee->name }}</span>
            </div>
        @endif

        {{-- <div class="org-id-card__divider" aria-hidden="true"></div> --}}
    </div>
</button>
