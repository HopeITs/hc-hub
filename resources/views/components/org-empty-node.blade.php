@props(['position','color'=>'gray','size'=>'md'])

@php
    $sizes = [
                'xs' => 'text-xs px-2 py-1 w-28',
        'sm' => 'text-xs px-2 py-1 w-32',
        'md' => 'text-sm px-3 py-2 w-44',
        'lg' => 'text-base px-4 py-3 w-52 font-bold',
    ];
@endphp

<div class="h-fit rounded-xl border border-{{ $color }}-200 bg-{{ $color }}-100 shadow-sm text-center {{ $sizes[$size] }}" data-tooltip-target="tooltip-default-{{$position->id}}">
    <div class="font-semibold truncate">
        {{  '—' }}
    </div>
    <div class="text-gray-600 text-[11px] truncate">
        {{ $position->title . ' - ' . $position->city->slug }}
    </div>

    <div id="tooltip-default-{{$position->id}}" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-400 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
        {{ $position->title . ' - ' . $position->city->slug }}
        <div class="tooltip-arrow" data-popper-arrow></div>
    </div>
</div>

