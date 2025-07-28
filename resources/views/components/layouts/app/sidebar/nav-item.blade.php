@props(['active' => false])


<li>
    <a {{ $attributes->merge([
    'class' => 'flex text-sm items-center w-full p-2 text-secondary-dark transition duration-75 rounded-lg pr-10 group hover:bg-gray-100 '. ($active ? ' !text-primary' : '')
    ]) }}>
        {{$slot}}
    </a>
</li>


