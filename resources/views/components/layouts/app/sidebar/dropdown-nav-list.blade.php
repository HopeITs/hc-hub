@props([
    'title' => 'Dropdown Menu',
    'icon' => 'fa-solid fa-chart-pie',
    'active' => false,
    'id' => 'dropdown-menu'
])

@php
    $isActive = $active ? 'block' : 'hidden';
    $ariaExpanded = $active ? 'true' : 'false';
@endphp

<li>
    <button
        type="button"
        class="flex items-center w-full p-2 text-base text-secondary-dark transition duration-75 rounded-lg group hover:bg-gray-100 {{ $isActive ? ' bg-secondary-soft' : ''  }}"
        aria-controls="{{$id}}"
        data-collapse-toggle="{{$id}}"
        aria-expanded="{{ $ariaExpanded }}"
    >
        <i class="{{ $icon }}"></i>
        <span class="text-base flex-1 ms-3 text-left rtl:text-right whitespace-nowrap">{{ $title }}</span>
        <svg
            class="w-3 h-3 transition-transform duration-200 {{ $active ? 'rotate-180' : '' }}"
            aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 10 6"
        >
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
        </svg>
    </button>
    <ul id="{{$id}}" class="{{ $isActive }} py-2 space-y-2">
        {{ $slot }}
    </ul>
</li>
