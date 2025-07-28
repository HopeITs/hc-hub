@props([
    'icon' => 'ti ti-layout-navbar',
     'active' => request()->is($attributes->get('href'))
])


<li>
    <a href="#"
        {{ $attributes->merge([
         'class' => 'flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group'])}}>
        <i class="{{ $icon }}"></i>
        <span class="ms-3">{{$slot}}</span>
    </a>
</li>
