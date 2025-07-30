<button
    type="submit"
    wire:loading.attr="disabled"
    {{ $attributes->merge(['class' => 'btn w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold']) }}>
    {{ $slot }}
</button>
