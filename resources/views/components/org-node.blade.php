@props(['position','color'=>'gray','size'=>'md' , 'employee' => null])

@php
    $sizes = [
        'xs' => 'text-xs px-2 py-1 w-28 text-[8px]',
        'sm' => 'text-xs px-2 py-2 w-36 text-[10px]',
        'md' => 'text-sm px-3 py-2 w-44',
        'lg' => 'text-base px-4 py-3 w-52',
    ];

    if(!isset($employee)){
        $employee = $position->employee();
    }

    $class =   "rounded-2xl   bg-white shadow-sm text-center {$sizes[$size]}" ;
@endphp

<a {{$attributes->merge([
    'class' => $class . ' h-fit block'
])}}

   href="{{route('employees.show',['employee' => $employee->username ?? 0])}}"

   data-tooltip-target="tooltip-default-{{$position->id.'-'.$employee->id}}">
    <div class="text-{{ $employee?->is_board_member ? 'primary font-bold' : 'secondary-medium font-semibold'}} truncate">
        {{  $employee->name ?? '—'  }}
    </div>
    <div class="text-secondary-medium  truncate">
        {{ $position->title . ' - ' . $position->city->slug }}
    </div>



</a>
<div id="tooltip-default-{{$position->id.'-'.$employee->id}}" role="tooltip"
     class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-400 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
    {{ $position->title . ' - ' . $position->city->slug }}
    <div class="tooltip-arrow" data-popper-arrow></div>
</div>
