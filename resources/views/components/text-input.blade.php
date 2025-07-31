@props([ 'type' => 'text'])

<div class="relative">
    {{-- Right icon (password toggle or mail icon) --}}
    @if ($type === 'password')
        <button
                type="button"
                data-password-toggle="{{ $id }}"
                class="absolute top-2.5 end-3 text-gray-400 hover:text-gray-600 cursor-pointer"
        >
            <i id="{{ $id }}_icon" class="fa-regular fa-eye-slash text-sm pointer-events-none"></i>
        </button>
    @elseif ($type === 'email')
        <span class="absolute top-2.5 end-3 text-gray-400">
            <i class="fa-regular fa-envelope text-sm"></i>
        </span>
    @endif

    {{-- Input --}}
    <input

            {{ $attributes->merge([
                'class' =>
                    'w-full pl-4 pr-4 py-2 text-sm text-gray-900 placeholder-gray-400 rounded-md border border-gray-300 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition'
            ])
            }}
    />
</div>
