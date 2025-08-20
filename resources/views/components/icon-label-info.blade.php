@props(['label' , 'info'])
<div {{$attributes->merge([
    'class' => 'flex items-center'
])}}>
    {{$slot}}
    <span class="font-bold me-1 text-sm 2xl:text-base">{{ $label. ': ' }}</span>
    <span class=" text-sm ">  {{$info}} </span>
</div>
