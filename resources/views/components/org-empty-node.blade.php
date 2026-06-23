@props(['position', 'size' => 'md', 'compact' => false])

@php
    $compactMap = ['lg' => 'sm', 'md' => 'xs', 'sm' => 'xs', 'xs' => 'xs'];
    $size = $compact ? ($compactMap[$size] ?? 'xs') : $size;

    $sizes = [
        'xs' => ['card' => 'w-32 px-2 pb-2 pt-2.5 text-[9px]', 'icon' => 'size-10'],
        'sm' => ['card' => 'w-36 px-2.5 pb-2.5 pt-3 text-[10px]', 'icon' => 'size-12'],
        'md' => ['card' => 'w-40 px-3 pb-3 pt-3.5 text-[10px]', 'icon' => 'size-14'],
        'lg' => ['card' => 'w-44 px-3.5 pb-3 pt-4 text-[11px]', 'icon' => 'size-16'],
    ];
    $style = $sizes[$size] ?? $sizes['md'];
    $location = $position->city?->name ?? $position->center?->name;
@endphp

<div {{ $attributes->merge([
    'class' => 'h-fit rounded-2xl border border-dashed border-gray-300 bg-gray-50/80 text-center text-secondary-medium transition hover:border-primary-200 hover:bg-primary-50/40 '.$style['card'],
]) }}>
    @if($position->program)
        <div class="mb-1.5 flex justify-center">
            <span class="max-w-full truncate rounded-full bg-gray-200/70 px-2 py-0.5 text-[8px] font-bold uppercase tracking-wide text-secondary-light">
                {{ $position->program->slug }}
            </span>
        </div>
    @endif

    <div class="flex flex-col items-center">
        <span class="flex {{ $style['icon'] }} shrink-0 items-center justify-center rounded-full border border-dashed border-gray-300 bg-white text-secondary-light shadow-sm">
            <x-icons.user class="size-5" />
        </span>
        <div class="w-full min-w-0 mt-2">
            <div class="font-extrabold truncate text-secondary-medium">@lang('general.vacant-position')</div>
            <div class="mt-0.5 line-clamp-2 min-h-6 font-semibold leading-tight">{{ $position->title }}</div>
        </div>
    </div>

    <div class="mt-1.5 flex items-center justify-center gap-1 truncate border-t border-dashed border-gray-200 pt-1.5 text-secondary-light">
        <x-icons.map-pin class="size-3 shrink-0" />
        <span class="truncate">{{ $location ?? __('general.not-specified') }}</span>
    </div>
</div>
