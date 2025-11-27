@props([
    'active' => false,
    'wireEvent' => null,
    'modelId' => null,
])

<input
    {{ $attributes->merge([
        'type' => 'checkbox',
        'role' => 'switch',
        'class' => '!text-primary rounded '
    ]) }}
    @checked((bool) $active)
    @if($wireEvent && $modelId)
        wire:click="{{ $wireEvent }}({{ $modelId }})"
    @endif
/>
