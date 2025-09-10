@props([
    'active' => 1,
    'modelId' => null,
    'wireEvent' => null, // required, e.g. 'toggleCityStatus'
])

@php
    $switchId = 'status-switch-' . $modelId;
@endphp

<input
    {{$attributes->merge([
    'type' => 'checkbox',
    'role' => 'switch',
    'class' => '!text-green-400 rounded border-green-400  me-1'
])}}
    @if($active !== "0") checked @endif
wire:click="{{ $wireEvent }}({{ $modelId }})"
>



