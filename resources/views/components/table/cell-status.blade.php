@props([
    'active' => true,
    'modelId' => null,
    'wireEvent' => null, // required, e.g. 'toggleCityStatus'
])

@php
    $switchId = 'status-switch-' . $modelId;
@endphp

<td class="px-5 py-2.5 text-gray-500">
    <div class="flex items-center form-check form-switch">
        <input
            type="checkbox"
            id="{{ $switchId }}"
            role="switch"
            @if($active) checked @endif
            wire:click="{{ $wireEvent }}({{ $modelId }})"
            class="text-green-400 rounded border-green-400  focus:ring-green-400 me-1"
        >
        <label for="{{ $switchId }}" class="ms-1 text-xs form-check-label">
            {{ $active ? __('general.active') : __('general.inactive') }}
        </label>
    </div>
</td>
