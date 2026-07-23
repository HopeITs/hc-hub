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
            group flex items-center w-full px-3 py-2.5 rounded-xl transition duration-150
            border-s-2 border-transparent

            font-medium text-secondary-light bg-transparent
            hover:text-secondary-dark hover:bg-secondary-soft

            {{ $active ? 'text-secondary-dark font-semibold bg-secondary-soft' : '' }}
        "
        :class="sidebarCollapsed && !open ? 'md:justify-center md:size-11 md:px-0 md:mx-auto' : ''"
    >
        {{-- Icon --}}
        <span class="flex items-center justify-center size-5 shrink-0 text-inherit group-hover:text-inherit">
            {{ $icon }}
        </span>

        {{-- Label --}}
        <span
            class="flex-1 text-[13px] text-left  ms-1.5 rtl:me-1.5 rtl:text-right whitespace-nowrap text-inherit group-hover:text-inherit"
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
