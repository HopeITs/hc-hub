<input
    {{ $attributes->merge([
        'type' => 'checkbox',
        'role' => 'switch',
        'class' => '!text-green-400 rounded border-green-400 me-1'
    ]) }}
    @checked((bool) $active)
    wire:click="{{ $wireEvent }}({{ $modelId }})"
/>

