@props(['active' => false])


<li>
    <a {{ $attributes->merge([
        'class' => 'relative flex text-sm
         items-center py-1 ps-9 ms-1 w-full text-secondary-dark font-light transition duration-75  group hover:font-bold hover:text-primary   border-s-2 transition-all transition-discrete ease-in-out duration-700  ' . ($active ? ' !text-primary border-primary !font-bold' : '')
    ]) }}>

            {{$slot}}


    </a>
</li>



