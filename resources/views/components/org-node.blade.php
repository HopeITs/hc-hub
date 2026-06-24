@props([
    'position',
    'size' => 'md',
    'employee' => null,
    'isManager' => false,
    'managerType' => 'direct',
    'compact' => false,
])

@php
    // Compact density shifts every card down one notch so far more fit on screen.
    $compactMap = ['lg' => 'sm', 'md' => 'xs', 'sm' => 'xs', 'xs' => 'xs'];
    $size = $compact ? $compactMap[$size] ?? 'xs' : $size;

    $sizes = [
        'xs' => [
            'card' => 'w-32 px-2 pb-2 pt-2.5',
            'avatar' => 'size-12',
            'name' => 'text-[11px]',
            'detail' => 'text-[9px]',
        ],
        'sm' => [
            'card' => 'w-36 px-2.5 pb-2.5 pt-3',
            'avatar' => 'size-14',
            'name' => 'text-xs',
            'detail' => 'text-[10px]',
        ],
        'md' => [
            'card' => 'w-40 px-3 pb-3 pt-3.5',
            'avatar' => 'size-16',
            'name' => 'text-sm',
            'detail' => 'text-[10px]',
        ],
        'lg' => [
            'card' => 'w-44 px-3.5 pb-3 pt-4',
            'avatar' => 'size-20',
            'name' => 'text-sm',
            'detail' => 'text-[11px]',
        ],
    ];

    $employee ??= $position->activeContracts->first()?->employee;
    $style = $sizes[$size] ?? $sizes['md'];
    $location = $position->city?->name ?? $position->center?->name;

    // The "dotted-line" manager is the OTHER reporting line from the structural one.
    $crossRelation = $managerType === 'direct' ? 'techManager' : 'directManager';
    $crossLabel = $managerType === 'direct' ? __('general.tech-manager') : __('general.direct-manager');
    $crossManager = $position->{$crossRelation};
    $crossEmployee = $crossManager?->activeContracts?->first()?->employee;

    // Board members get a full brand-red border; managers a soft red; others gray.
    $border = $employee->is_board_member
        ? 'border-primary ring-1 ring-primary/40'
        : ($isManager
            ? 'border-primary-200 ring-1 ring-primary-100'
            : 'border-gray-200');
    $cardClass =
        "group relative block h-fit overflow-hidden rounded-2xl border bg-white text-center shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-primary-200 hover:shadow-lg {$style['card']} " .
        $border;
@endphp

<button type="button" wire:click="$dispatch('open-profile', { employeeId: {{ $employee->id }} })"
    aria-label="{{ __('general.view-profile-of', ['name' => $employee->name]) }}"
    {{ $attributes->merge(['class' => $cardClass]) }}>
    <div class="absolute inset-x-0 top-0 h-1 {{ $isManager ? 'bg-primary' : 'bg-secondary-soft' }}"></div>

    @if ($position->program)
        <div class="mb-1.5 flex justify-center">
            <span
                class="max-w-full truncate rounded-full bg-secondary-soft/15 px-2 py-0.5 text-[8px] font-bold uppercase tracking-wide text-secondary-dark">
                {{ $position->program->slug }}
            </span>
        </div>
    @endif

    <div class="flex flex-col items-center">
        <div class="relative">
            <img src="{{ $employee->getFirstMediaUrl('avatar') }}" alt="{{ $employee->name }}" loading="lazy"
                class="{{ $style['avatar'] }} rounded-full object-fit object-center ring-4 ring-white shadow-md outline outline-1 outline-gray-200 transition group-hover:outline-primary-300">
            <span class="absolute bottom-0.5 end-0.5 size-2.5 rounded-full border-2 border-white bg-emerald-500"></span>
        </div>

        <div class="w-full min-w-0 mt-2">
            <div
                class="{{ $style['name'] }} truncate font-extrabold {{ $employee->is_board_member ? 'text-primary' : 'text-secondary-dark' }}">
                {{ $employee->name }}
            </div>
            <div
                class="{{ $style['detail'] }} mt-0.5 line-clamp-2 min-h-6 font-semibold leading-tight text-secondary-medium">
                {{ $position->title }}
            </div>
        </div>
    </div>

    <div
        class="{{ $style['detail'] }} mt-1.5 flex items-center justify-center gap-1 truncate border-t border-gray-100 pt-1.5 text-secondary-light">
        <span class="flex items-center justify-center min-w-0 gap-1 truncate">
            <x-icons.map-pin class="size-3 shrink-0" />
            <span class="truncate">{{ $location ?? __('general.not-specified') }}</span>
        </span>
    </div>

    {{-- Matrix dotted-line: the manager on the OTHER reporting line. --}}
    @if ($crossEmployee)
        <div class="{{ $style['detail'] }} mt-1 flex items-center justify-center gap-1 truncate rounded-lg border border-dashed border-amber-300 bg-amber-50/70 px-1.5 py-0.5 text-amber-700"
            title="{{ $crossLabel }}: {{ $crossEmployee->name }}">
            <svg class="size-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-dasharray="3 2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16" />
            </svg>
            <span class="font-semibold truncate">{{ $crossEmployee->name }}</span>
        </div>
    @endif
</button>
