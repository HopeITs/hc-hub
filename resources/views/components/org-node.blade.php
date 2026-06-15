@props(['position', 'size' => 'md', 'employee' => null, 'isManager' => false])

@php
    $sizes = [
        'xs' => ['card' => 'w-32 px-2.5 pb-2.5 pt-3', 'avatar' => 'size-14', 'name' => 'text-[11px]', 'detail' => 'text-[9px]'],
        'sm' => ['card' => 'w-36 px-3 pb-3 pt-3.5', 'avatar' => 'size-16', 'name' => 'text-xs', 'detail' => 'text-[10px]'],
        'md' => ['card' => 'w-40 px-3 pb-3 pt-4', 'avatar' => 'size-18', 'name' => 'text-sm', 'detail' => 'text-[10px]'],
        'lg' => ['card' => 'w-44 px-3.5 pb-3.5 pt-4', 'avatar' => 'size-22', 'name' => 'text-sm', 'detail' => 'text-[11px]'],
    ];

    $employee ??= $position->activeContracts->first()?->employee;
    $style = $sizes[$size] ?? $sizes['md'];
    $location = $position->city?->name ?? $position->center?->name;
    $profileUrl = filled($employee->username)
        ? route('employees.show', ['employee' => $employee->username])
        : null;
    $cardClass = "group relative block h-fit overflow-hidden rounded-2xl border bg-white text-center shadow-sm transition duration-200 {$style['card']} "
        .($isManager ? 'border-primary-200 ring-1 ring-primary-100' : 'border-gray-200')
        .($profileUrl ? ' hover:-translate-y-0.5 hover:border-primary-200 hover:shadow-lg' : '');
@endphp

@if($profileUrl)
    <a href="{{ $profileUrl }}"
       wire:navigate
       aria-label="{{ __('general.view-profile-of', ['name' => $employee->name]) }}"
       {{ $attributes->merge(['class' => $cardClass]) }}>
@else
    <div {{ $attributes->merge(['class' => $cardClass]) }}>
@endif
    <div class="absolute inset-x-0 top-0 h-1 {{ $isManager ? 'bg-primary' : 'bg-secondary-soft' }}"></div>

    <div class="flex flex-col items-center">
        <div class="relative">
            <img src="{{ $employee->getFirstMediaUrl('avatar') }}"
                 alt="{{ $employee->name }}"
                 loading="lazy"
                 class="{{ $style['avatar'] }} rounded-full object-cover object-center ring-4 ring-white shadow-md outline outline-1 outline-gray-200 transition group-hover:outline-primary-300">
            <span class="absolute bottom-1 end-1 size-3 rounded-full border-2 border-white bg-emerald-500"></span>
        </div>

        <div class="mt-2.5 min-w-0 w-full">
            <div class="{{ $style['name'] }} truncate font-extrabold {{ $employee->is_board_member ? 'text-primary' : 'text-secondary-dark' }}">
                {{ $employee->name }}
            </div>
            <div class="{{ $style['detail'] }} mt-1 line-clamp-2 min-h-7 font-semibold leading-tight text-secondary-medium">
                {{ $position->title }}
            </div>
        </div>
    </div>

    <div class="{{ $style['detail'] }} mt-2 flex items-center justify-center gap-1 truncate border-t border-gray-100 pt-2 text-secondary-light">
        <span class="flex min-w-0 items-center justify-center gap-1 truncate">
            <x-icons.map-pin class="size-3.5 shrink-0" />
            <span class="truncate">{{ $location ?? __('general.not-specified') }}</span>
        </span>
    </div>
@if($profileUrl)
    </a>
@else
    </div>
@endif
