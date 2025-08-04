@props(['active' => false])



<li>
    <a {{ $attributes->merge([
             'class' => $active ? 'flex items-center w-full p-2 text-base text-secondary-dark transition duration-75 rounded-lg group hover:bg-gray-100  bg-gray-100' :  'flex items-center w-full p-2 text-base text-secondary-dark transition duration-75 rounded-lg group hover:bg-gray-100'   ])
    }}
    >
        {{ $icon ?? '' }}
        <span class="text-sm font-extrabold flex-1 ms-3 text-left rtl:text-right whitespace-nowrap">{{ $slot }}</span>

    </a>
</li>
