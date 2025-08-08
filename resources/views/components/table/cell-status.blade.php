@props([
    'active' => true,
    'modelId' => null,
    'wireEvent' => null, // required, e.g. 'toggleCityStatus'
])

@php
    $switchId = 'status-switch-' . $modelId;
@endphp

<td class="px-5 py-2.5 text-gray-500 cursor-pointer  text-right">
    <div class="flex items-center form-check form-switch">
        <x-forms.toggle-input :modelId="$modelId" :active="$active"  :wireEvent="$wireEvent"/>

        <label for="{{ $switchId }}" class="ms-1 text-xs form-check-label">
            {{ $active ? __('general.active') : __('general.inactive') }}
        </label>
    </div>
</td>
