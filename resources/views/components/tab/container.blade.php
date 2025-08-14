@props(['activeTab'])
<div {{$attributes->merge([
    'class' => 'p-4 border rounded-md'
])}}
     x-show="activeTab === '{{$activeTab}}'" >
    {{$slot}}
</div>
