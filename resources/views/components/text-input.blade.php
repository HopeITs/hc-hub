@props(['id', 'type' => 'text', 'model', 'placeholder' => ''])

<div class="relative">

    {{-- Right icon (password toggle or mail icon) --}}
    @if ($type === 'password')
        <button
            type="button"
            onclick="togglePasswordVisibility('{{ $id }}')"
            class="absolute top-2.5 end-3 text-gray-400 hover:text-gray-600"
        >
            <i id="{{ $id }}_icon" class="ti ti-eye-off text-base"></i>
        </button>
    @elseif ($type === 'email')
        <span class="absolute top-2.5 end-3 text-gray-400">
            <i class="ti ti-mail text-base"></i>
        </span>
    @endif

    {{-- Input --}}
    <input
        id="{{ $id }}"
        type="{{ $type }}"
        placeholder="{{ $placeholder }}"
        autocomplete="{{ $id }}"
        required
        {{ $attributes->merge([
            'class' =>
                'w-full pl-4  py-2 text-sm text-gray-900 placeholder-gray-400 rounded-md border border-gray-300 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition'
        ]) }}
    />
</div>
