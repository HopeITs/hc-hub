@props([
    'active' => true,
    'modelId' => null,
    'wireEvent' => null, // required, e.g. 'toggleCityStatus'
])

@php
    $switchId = 'status-switch-' . $modelId;
@endphp
<input
    type="checkbox"
    id="{{ $switchId }}"
    role="switch"
    @if($active) checked @endif
    wire:click="{{ $wireEvent }}({{ $modelId }})"
    class="text-green-400 rounded border-green-400  focus:ring-green-400 me-1"
>
