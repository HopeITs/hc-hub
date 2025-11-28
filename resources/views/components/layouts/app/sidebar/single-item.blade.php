@props(['active' => false, 'icon' => null])

<li>
    <a
        {{ $attributes->merge([
            'class' => "
                group flex items-center w-full p-2 rounded-lg transition duration-150
                font-medium text-secondary-light bg-white
                hover:text-secondary-dark hover:font-bold hover:bg-secondary-soft
                " . ($active ? ' !text-primary !font-bold' : '')
        ]) }}
    >

        {{-- Icon --}}
        <span class="size-5 shrink-0 text-inherit group-hover:text-inherit">
            {!! $icon !!}
        </span>

        {{-- Label --}}
        <span
            class="
                text-sm flex-1 ms-3 rtl:me-3 text-left rtl:text-right whitespace-nowrap
                text-inherit group-hover:text-inherit
            "
        >
            {{ $slot }}
        </span>

    </a>
</li>
