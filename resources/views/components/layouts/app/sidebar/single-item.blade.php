@props(['active' => false, 'icon' => null])

<li class="w-full">
    <a
        title="{{ trim(strip_tags($slot)) }}"
        {{ $attributes->merge([
            'class' => "
                group flex items-center w-full px-3 py-2.5 rounded-xl transition duration-150
                border-s-2 border-transparent
                font-medium text-secondary-light bg-transparent
                hover:text-secondary-dark hover:bg-secondary-soft
                " . ($active ? ' !text-primary !font-semibold bg-primary/5' : '')
        ]) }}
        :class="sidebarCollapsed && !open ? 'md:justify-center md:size-11 md:px-0 md:mx-auto' : ''"
    >

        {{-- Icon --}}
        <span class="flex items-center justify-center size-5 shrink-0 text-inherit group-hover:text-inherit">
            {!! $icon !!}
        </span>

        {{-- Label --}}
        <span
            class="
                text-[13px] flex-1 ms-1.5 rtl:me-1.5 text-left rtl:text-right whitespace-nowrap
                text-inherit group-hover:text-inherit
            "
            x-show="!sidebarCollapsed || open"
        >
            {{ $slot }}
        </span>

    </a>
</li>
