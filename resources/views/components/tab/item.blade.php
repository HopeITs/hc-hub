@props(['title' , 'active' => false])
<li>
    <a {{$attributes->merge([
    'class' => 'w-full flex items-center p-3 rounded-lg'. $active ? 'bg-primary text-white' : 'bg-gray-50 hover:bg-gray-100'
])}}

>
        {{$slot}}
        <span class="ms-2 font-semibold">{{$title}}</span>
    </a>
</li>
