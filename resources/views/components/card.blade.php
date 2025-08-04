@props(['title' => null])
<div {{$attributes->merge([
    'class' => 'max-w-sm p-6 bg-white border border-red-200 rounded-lg shadow-sm'
])}}
>
    {{$icon ?? ''}}
    @isset($title)
        <h5 class="mb-2 text-2xl font-semibold tracking-tight text-gray-900 ">{{$title}}</h5>
    @endisset
    <p class="mb-3 font-normal text-gray-500 line-clamp-5 ">
    {{$slot}}
    </p>


</div>
