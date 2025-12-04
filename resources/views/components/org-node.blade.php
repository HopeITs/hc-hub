@props(['position','color'=>'gray','size'=>'md' , 'employee' => null])

@php
    $sizes = [
        'xs' => 'text-xs px-2 py-1 w-28',
        'sm' => 'text-xs px-2 py-1 w-32',
        'md' => 'text-sm px-3 py-2 w-44',
        'lg' => 'text-base px-4 py-3 w-52 font-bold',
    ];

    if(!isset($employee)){
        $employee = $position->employee();
    }

    $class = $employee?->is_board_member ?  "rounded-xl border border-primary  bg-white shadow-sm text-center {$sizes[$size]}" :  "rounded-xl border border-{$color}  bg-white shadow-sm text-center {$sizes[$size]}" ;
@endphp

<a {{$attributes->merge([
    'class' => $class
])}}

   href="{{route('employees.show',['employee' => $employee->username ?? 0])}}"

   data-tooltip-target="tooltip-default-{{$position->id}}">
    <div class="font-semibold text-{{ $employee?->is_board_member ? 'primary' : 'secondary-dark'}} truncate">
        {{  $employee->name ?? '—'  }}
    </div>
    <div class="text-secondary-medium text-[11px] truncate">
        {{ $position->title . ' - ' . $position->city->slug }}
    </div>
</a>
<div id="tooltip-default-{{$position->id}}" role="tooltip"
     class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-400 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
    {{ $position->title . ' - ' . $position->city->slug }}
    <div class="tooltip-arrow" data-popper-arrow></div>
</div>
