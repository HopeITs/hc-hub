@props([
    'type' => 'text',
    'label' => '',
    'id' => null,
    'autocomplete' => '',
])

@php
    use Illuminate\Support\Str;
    $inputId = $id ?? Str::slug($attributes->wire('model') ?? 'input');
@endphp

<div class="space-y-1">
    @if ($label)
        <x-settings.input-label for="{{ $inputId }}" :value="$label" />
    @endif

    <div class="relative">
        <input
            id="{{ $inputId }}"
            type="{{ $type }}"
            autocomplete="{{ $autocomplete }}"
            {{ $attributes->merge([
                'class' => 'bg-white border border-borderColor text-sm rounded-input block w-full py-2 px-2.5 h-[38px] placeholder:text-gray-400 pr-2',
            ]) }}
        />


        {{-- Password visibility toggle --}}
        @if ($type === 'password')
            <span
                class="ti ti-eye-off absolute top-[10px] left-[10px] cursor-pointer toggle-password"
                onclick="togglePasswordVisibility('{{ $inputId }}', this)">
            </span>
        @endif
    </div>

    <x-settings.input-error :field="$attributes->wire('model')" />
</div>
