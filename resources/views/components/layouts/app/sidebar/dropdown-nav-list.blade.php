@props([
    'title' => 'Dropdown Menu',
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
        aria-controls="{{ $id }}"
        data-collapse-toggle="{{ $id }}"
        aria-expanded="{{ $ariaExpanded }}"

        class=" cursor-pointer
            group flex items-center w-full p-2 rounded-lg transition duration-150

            font-medium text-secondary-light bg-white
            hover:text-secondary-dark hover:font-bold hover:bg-secondary-soft

            {{ $active ? 'text-secondary-dark font-bold bg-secondary-soft' : '' }}
        "
    >
        {{-- Icon --}}
        <span class="size-4 shrink-0 text-inherit group-hover:text-inherit">
            {{ $icon }}
        </span>

        {{-- Label --}}
        <span
            class="
                text-sm flex-1 ms-3 rtl:me-3 text-left rtl:text-right whitespace-nowrap
                text-inherit group-hover:text-inherit
            "
        >
            {{ $title }}
        </span>

        {{-- Chevron --}}
        <svg
            class="
                size-3 shrink-0 text-inherit group-hover:text-inherit
            "
            aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg"
            fill="none" viewBox="0 0 24 24"
            stroke="currentColor" stroke-width="1.9"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
        </svg>
    </button>

    <ul id="{{ $id }}" class="{{ $isActive }} ps-4">
        {{ $list }}
    </ul>
</li>
