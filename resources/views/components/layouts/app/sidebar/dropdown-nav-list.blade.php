@props([
    'title' => 'Dropdown Menu',
    'active' => false,
    'id' => 'dropdown-menu'
])

@php
    $isActive = $active ? 'block' : 'hidden';
    $ariaExpanded = $active ? 'true' : 'false';
@endphp

<li class="w-full">
    <button
        type="button"
        aria-controls="{{ $id }}"
        data-collapse-toggle="{{ $id }}"
        aria-expanded="{{ $ariaExpanded }}"
        title="{{ $title }}"

        class=" cursor-pointer
            group flex items-center w-full p-2 rounded-lg transition duration-150

            font-medium text-secondary-light bg-white
            hover:text-secondary-dark hover:font-bold hover:bg-secondary-soft

            {{ $active ? 'text-secondary-dark font-bold bg-secondary-soft' : '' }}
        "
        :class="sidebarCollapsed && !open ? 'md:justify-center md:size-11 md:px-0' : ''"
    >
        {{-- Icon --}}
        <span class="size-5 shrink-0 text-inherit group-hover:text-inherit">
            {{ $icon }}
        </span>

        {{-- Label --}}
        <span
            class="flex-1 text-sm text-left  ms-3 rtl:me-3 rtl:text-right whitespace-nowrap text-inherit group-hover:text-inherit"
            x-show="!sidebarCollapsed || open"
        >
            {{ $title }}
        </span>

        {{-- Chevron --}}
        <svg
            class=" size-3 shrink-0 text-inherit group-hover:text-inherit"
            x-show="!sidebarCollapsed || open"
            aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg"
            fill="none" viewBox="0 0 24 24"
            stroke="currentColor" stroke-width="1.9"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
        </svg>
    </button>

    <ul id="{{ $id }}" class="{{ $isActive }} ps-4" x-show="!sidebarCollapsed || open">
        {{ $list }}
    </ul>
</li>
