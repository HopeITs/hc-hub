@props(['active' => false])


<li>
    <a {{ $attributes->merge([
        'class' => 'relative flex text-xs
         items-center ps-6 py-2 w-full text-secondary-dark font-light transition duration-75  group hover:font-bold hover:text-primary   border-s-2 transition-all transition-discrete ease-in-out duration-700  ' . ($active ? ' !text-primary border-primary !font-bold' : '')
    ]) }}>

            {{$slot}}


    </a>
</li>



