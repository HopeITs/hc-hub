@props(['position','color'=>'gray','size'=>'md'])

@php
    $sizes = [
        'sm' => 'text-xs px-2 py-1 w-32',
        'md' => 'text-sm px-3 py-2 w-44',
        'lg' => 'text-base px-4 py-3 w-52 font-bold',
    ];
@endphp

<div class="rounded-xl border border-{{ $color }}-400 bg-{{ $color }}-50 shadow-sm text-center {{ $sizes[$size] }}">
    <div class="font-semibold truncate">
        {{ $position->employee()?->name ?? '—' }}
    </div>
    <div class="text-gray-600 text-[13px] truncate">
        {{ $position->title }}
    </div>
</div>
