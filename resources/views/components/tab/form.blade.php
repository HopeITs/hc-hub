@props([
    'activeTab'
])
<div {{$attributes->merge([
    'class' => 'flex flex-col md:flex-row gap-8'
])}}
>
    {{$slot}}

</div>
