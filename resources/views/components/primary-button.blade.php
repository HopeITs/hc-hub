<button

    wire:loading.attr="disabled"
    {{ $attributes->merge([
        'class' =>
            'w-full py-2 px-4 text-sm font-semibold text-white bg-primary hover:opacity-90 cursor-pointer rounded-md shadow-sm transition duration-150 ease-in-out flex justify-center items-center',
        'type' => 'submit'
    ]) }}
>
    {{ $slot }}
</button>
