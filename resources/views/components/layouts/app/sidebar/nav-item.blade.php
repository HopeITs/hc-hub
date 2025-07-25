@props(['active' => false])


<li>
    <a {{ $attributes->merge([
    'class' => 'relative flex items-center w-full text-xs leading-normal p-2 ps-8 text-gray-500 hover:text-primary before:absolute before:right-[13px] before:top-1/2 before:-translate-y-1/2 before:w-0.5 before:h-[18px] before:bg-primary before:opacity-0'. ($active ? 'active' : '')
    ]) }}>
        {{$slot}}
    </a>
</li>
