@props([
    'title' => 'Dropdown Menu',
    'icon' => 'ti ti-layout-grid-add'
])
<li class="submenu mb-3">
    <a href="javascript:void(0);"
       class="relative flex item-center w-full p-2 text-sm leading-normal font-medium text-gray-900 group hover:bg-dark-transparent transition-all duration-500 ease-in-out rounded-[5px] ">
        <i class="{{$icon}}"></i><span class="ms-2">{{$title}}</span>
        <span
            class="menu-arrow absolute top-1/2 -translate-y-1/2 left-2.5 right-auto flex items-center w-4 h-4 transition-all duration-200 ease-in-out before:absolute before:top-[5px] before:left-[5px] before:w-1.5 before:h-1.5 before:border-r-2 before:border-b-2 before:border-gray-900 before:rotate-45"></span>
    </a>
    <ul class="relative mt-2 before:absolute before:top-0 before:right-3.5 before:w-[1.5px] before:h-full before:bg-gray-100">
        {{$slot}}
    </ul>
</li>
