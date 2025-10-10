@props(['position','color'=>'gray','size'=>'md' , 'employee' => null])

@php
    $sizes = [
        'sm' => 'text-xs px-2 py-1 w-32',
        'md' => 'text-sm px-3 py-2 w-44',
        'lg' => 'text-base px-4 py-3 w-52 font-bold',
    ];

    if(!isset($employee)){
        $employee = $position->employee();
    }
@endphp

<div class="rounded-xl border border-{{  $employee?->is_board_member ? 'primary' : $color}} bg-white shadow-sm text-center {{ $sizes[$size] }}" data-tooltip-target="tooltip-default-{{$position->id}}">
    <div class="font-semibold text-{{ $employee?->is_board_member ? 'primary' : 'secondary-dark'}} truncate">
        {{  $employee->name ?? '—'  }}
    </div>
    <div class="text-secondary-medium text-[11px] truncate">
        {{ $position->title }}
    </div>
</div>
<div id="tooltip-default-{{$position->id}}" role="tooltip" class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-400 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
    {{ $position->title }}
    <div class="tooltip-arrow" data-popper-arrow></div>
</div>
