@props(['title' => null])
<div {{$attributes->merge([
    'class' => ' p-5 bg-white flex flex-col justify-between border border-neutral-200 rounded-surface shadow-e1'
])}}
>

    @isset($title)
        <div class="flex justify-between ">

            <h5 class="mb-3 text-h4 font-extrabold text-start line-clamp-2 text-neutral-800 w-2/3">{{$title}}</h5>
            {{$icon ?? ''}}
        </div>

    @endisset
    <p class="mb-3 font-normal text-secondary-medium line-clamp-3 ">
    {{$slot}}
    </p>


</div>
