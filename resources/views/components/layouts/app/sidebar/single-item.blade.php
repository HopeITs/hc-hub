@props(['active' => false])

<li>
    <a {{ $attributes->merge([
             'class' =>'relative flex text-base
         items-center p-2 w-full text-secondary-dark  transition duration-75 rounded-lg group hover:font-extrabold hover:text-primary   transition-all transition-discrete ease-in-out duration-700  ' . ($active ? ' !text-primary !font-bold  bg-gray-100' : '')
         ]) }}
    >
        {{ $icon ?? '' }}
        <span class=" flex-1 ms-3 text-left rtl:text-right whitespace-nowrap">{{ $slot }}</span>

    </a>
</li>
