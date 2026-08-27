@props(['disabled' => false])

<div wire:ignore class="w-full">
    <select {{ $attributes->merge(['disabled' => $disabled]) }}>
        {{ $slot }}
    </select>
</div>
