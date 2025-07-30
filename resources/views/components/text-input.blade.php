@props(['id', 'type' => 'text', 'model', 'placeholder' => ''])

<div class="relative">

    @if ($type === 'password')
        <button
            type="button"
            onclick="togglePasswordVisibility('{{ $id }}')"
            class="absolute top-[10px] right-[10px] z-10 text-gray-400 hover:text-gray-600">
            <i id="{{ $id }}_icon" class="ti ti-eye-off text-base"></i>
        </button>
    @endif


    @if ($type === 'email')
        <span class="absolute top-[10px] right-[10px]">
            <i class="ti ti-mail text-gray-400"></i>
        </span>
    @endif

    <input
        id="{{ $id }}"
        type="{{ $type }}"
        placeholder="{{ $placeholder }}"
        autocomplete="{{ $id }}"
        required
        {{ $attributes->merge([
            'class' => 'bg-gray-100 border border-borderColor text-sm rounded-input block w-full py-2 px-9 h-[38px] placeholder:text-gray-400'
        ]) }}
    />
</div>
