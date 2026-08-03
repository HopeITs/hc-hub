@props([
    'id',
    'wireModel',
    'format' => 'HH:mm',
    'disabled' => false,
])

@php($pickerId = $id . '_picker')

<input
    id="{{ $id }}"
    type="hidden"
    wire:model.defer="{{ $wireModel }}"
/>

<div class="relative w-full" wire:ignore>
    <clock-timepicker
        id="{{ $pickerId }}"
        data-bind-target="{{ $id }}"
        format="{{ $format }}"
        @disabled($disabled)
        class="block w-full min-h-[2.375rem]"
    ></clock-timepicker>

    <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3">
        <x-icons.clock class="size-4 text-secondary-light" />
    </div>
</div>
