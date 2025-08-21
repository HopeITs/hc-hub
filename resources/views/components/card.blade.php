@props(['title' => null])
<div {{$attributes->merge([
    'class' => ' p-6 bg-white flex flex-col justify-between border border-gray-200 rounded-lg shadow-sm'
])}}
>

    @isset($title)
        <div class="flex justify-between ">
            {{$icon ?? ''}}
            <h5 class="mb-2 text-xl font-semibold text-start line-clamp-2 tracking-tight text-gray-900 w-2/3 ">{{$title}}</h5>
        </div>

    @endisset
    <p class="mb-3 font-normal text-gray-500 line-clamp-3 ">
    {{$slot}}
    </p>


</div>
