@props([
    'active' => false,
    'wireEvent' => null,
    'modelId' => null,
])

<input
    {{ $attributes->merge([
        'type' => 'checkbox',
        'class' => 'checkbox-transparent'
    ]) }}
    @checked((bool) $active)
    @if($wireEvent && $modelId)
        wire:click="{{ $wireEvent }}({{ $modelId }})"
    @endif
/>

<style>

    .checkbox-transparent {
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        outline: none !important;
        border-radius: 25%;
    }


    .checkbox-transparent:checked {
        background-color: transparent !important;
        box-shadow: none !important;
    }


    .checkbox-transparent:checked::after {
        content: "✔";
        font-size: 11px;
        color: var(--color-primary, #d00);
        border: 1px solid var(--color-primary) !important;
        padding: 0 4px ;
        border-radius: 25%;
        font-weight: bold;

    }





</style>
