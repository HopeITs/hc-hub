{{-- Internal links swap pages via wire:navigate; pass :wire:navigate="false" to opt out. --}}
<a

    wire:loading.attr="disabled"
    {{ $attributes->merge([
        'class' =>
            'w-full min-h-11 py-2 px-[18px] text-body font-bold text-white bg-primary cursor-pointer rounded-control transition duration-150 ease-in-out flex justify-center items-center gap-2 hover:opacity-90 active:scale-[0.98]',
        'wire:navigate' => true,
    ]) }}
>
    {{ $slot }}
    </a>
