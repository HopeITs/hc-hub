@props(['id', 'type' => 'text', 'model', 'placeholder'])

<div class="relative">
    <input
        id="{{ $id }}"
        type="{{ $type }}"
        wire:model.defer="{{ $model }}"
        placeholder="{{ $placeholder }}"
        autocomplete="{{ $id }}"
        required
        {{ $attributes->merge(['class' => 'bg-gray-100 border border-borderColor text-sm rounded-input block w-full py-2 px-2.5 h-[38px] placeholder:text-gray-400']) }}
    />
    @if ($type === 'email')
        <span class="absolute top-[10px] right-[10px]">
            <i class="ti ti-mail text-gray-400"></i>
        </span>
    @elseif ($type === 'password')
        <span class="ti ti-eye-off absolute top-[10px] right-[10px] cursor-pointer toggle-password"></span>
    @endif
</div>
