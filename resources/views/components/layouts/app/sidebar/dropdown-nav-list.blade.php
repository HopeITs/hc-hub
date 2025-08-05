@props([
    'title' => 'Dropdown Menu',
    'active' => false,
    'id' => 'dropdown-menu'
])

@php
    $isActive = $active ? 'block' : 'hidden';
    $ariaExpanded = $active ? 'true' : 'false';
@endphp

<li >
    <button
        type="button"
        class="flex items-center w-full p-2 text-base text-secondary-dark transition duration-75 rounded-lg group hover:bg-gray-100 {{$active ? ' bg-gray-100' : ''}}"
        aria-controls="{{$id}}"
        data-collapse-toggle="{{$id}}"
        aria-expanded="{{$ariaExpanded}}"

    >
       {{$icon}}
        <span class="text-sm font-extrabold flex-1 ms-3 text-left rtl:text-right whitespace-nowrap">{{ $title }}</span>
        <svg class="size-3 text-secondary-dark text-extrabold" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="m19 9-7 7-7-7"/>
        </svg>

        {{--        <i class="fa-solid {{ $active ? 'fa-chevron-down' : 'fa-chevron-up' }} "></i>--}}
    </button>
    <ul id="{{$id}}" class="{{ $isActive }} px-4  ">
        {{ $list }}
    </ul>
</li>




