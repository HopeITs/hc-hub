@props(['label' , 'info'])
<div {{$attributes->merge([
    'class' => 'flex items-center'
])}}>
    {{$slot}}
    <span class="font-bold me-1 text-body 2xl:text-body">{{ $label. ': ' }}</span>
    <span class=" text-body  2xl:text-body">  {{$info}} </span>
</div>
