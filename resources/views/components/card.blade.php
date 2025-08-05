@props(['title' => null])
<div {{$attributes->merge([
    'class' => ' p-6 bg-white border border-gray-200 rounded-lg shadow-sm'
])}}
>
    {{$icon ?? ''}}
    @isset($title)
        <h5 class="mb-2 text-2xl font-semibold tracking-tight text-gray-900 ">{{$title}}</h5>
    @endisset
    <p class="mb-3 font-normal text-gray-500 line-clamp-3 ">
    {{$slot}}
    </p>


</div>
