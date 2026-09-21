@props([
    'id',
    'wireModel' => null,
    'alpineModel' => null,
    'format' => 'HH:mm',
    'disabled' => false,
    'min' => null,
    'max' => null,
])

@php($pickerId = $id . '_picker')

<input
    id="{{ $id }}"
    type="hidden"
    @if($wireModel) wire:model.defer="{{ $wireModel }}" @endif
    @if($alpineModel) x-model="{{ $alpineModel }}" @endif
/>

<div
    class="relative w-full"
    wire:ignore
    @if($alpineModel)
        x-effect="$nextTick(() => {
            const picker = document.getElementById('{{ $pickerId }}');
            const value = {{ $alpineModel }} || '';
            if (picker && picker.value !== value) picker.value = value;
        })"
    @endif
>
    <clock-timepicker
        id="{{ $pickerId }}"
        data-bind-target="{{ $id }}"
        format="{{ $format }}"
        @if($min) minimum="{{ $min }}" @endif
        @if($max) maximum="{{ $max }}" @endif
        @disabled($disabled)
        class="block w-full min-h-[2.375rem]"
    ></clock-timepicker>

    <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3">
        <x-icons.clock class="size-4 text-secondary-light" />
    </div>
</div>
