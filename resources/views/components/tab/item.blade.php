@props(['title' , 'active' => false])
<li>
    <a {{$attributes->merge([
    'class' => $active ? 'w-full flex items-center p-3 rounded-lg bg-primary text-white' : 'w-full flex items-center p-3 rounded-lg bg-gray-50 hover:bg-gray-100'
])}}

>
        {{$slot}}
        <span class="ms-2 font-semibold">{{$title}}</span>
    </a>
</li>
