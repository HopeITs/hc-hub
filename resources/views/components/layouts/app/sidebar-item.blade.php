@props([
    'icon' => 'ti ti-layout-navbar',
     'active' => request()->is($attributes->get('href'))
])
<li class="mb-[5px]">
    <a {{ $attributes->merge([
        'class' => 'relative flex item-center w-full p-2 text-sm leading-normal font-medium ' .
            ($active ? 'text-primary bg-light' : 'text-gray-900') .
            ' group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px]'
    ]) }}>
        <i class="{{ $icon }}"></i><span class="ms-2">{{ $slot }}</span>
    </a>
</li>
