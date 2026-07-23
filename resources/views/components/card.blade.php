@props(['title' => null])
<div {{$attributes->merge([
    'class' => ' p-6 bg-white flex flex-col justify-between border border-neutral-100 rounded-surface shadow-e1'
])}}
>

    @isset($title)
        <div class="flex justify-between ">

            <h5 class="mb-2 text-h4 font-semibold text-start line-clamp-2 tracking-tight text-neutral-900 w-2/3  ">{{$title}}</h5>
            {{$icon ?? ''}}
        </div>

    @endisset
    <p class="mb-3 font-normal text-secondary-medium line-clamp-3 ">
    {{$slot}}
    </p>


</div>
