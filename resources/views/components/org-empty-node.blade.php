@props(['position', 'size' => 'md'])

@php
    $sizes = [
        'xs' => 'w-32 px-2.5 pb-2.5 pt-3 text-[9px]',
        'sm' => 'w-36 px-3 pb-3 pt-3.5 text-[10px]',
        'md' => 'w-40 px-3 pb-3 pt-4 text-[10px]',
        'lg' => 'w-44 px-3.5 pb-3.5 pt-4 text-[11px]',
    ];
    $location = $position->city?->name ?? $position->center?->name;
@endphp

<div {{ $attributes->merge([
    'class' => 'h-fit rounded-2xl border border-dashed border-gray-300 bg-gray-50/80 text-center text-secondary-medium transition hover:border-primary-200 hover:bg-primary-50/40 '.($sizes[$size] ?? $sizes['md']),
]) }}>
    <div class="flex flex-col items-center">
        <span class="flex size-16 shrink-0 items-center justify-center rounded-full border border-dashed border-gray-300 bg-white text-secondary-light shadow-sm">
            <x-icons.user class="size-7" />
        </span>
        <div class="mt-2.5 min-w-0 w-full">
            <div class="truncate font-extrabold text-secondary-medium">@lang('general.vacant-position')</div>
            <div class="mt-1 line-clamp-2 min-h-7 font-semibold leading-tight">{{ $position->title }}</div>
        </div>
    </div>

    <div class="mt-2 flex items-center justify-center gap-1 truncate border-t border-dashed border-gray-200 pt-2 text-secondary-light">
        <x-icons.map-pin class="size-3.5 shrink-0" />
        <span class="truncate">{{ $location ?? __('general.not-specified') }}</span>
    </div>
</div>
