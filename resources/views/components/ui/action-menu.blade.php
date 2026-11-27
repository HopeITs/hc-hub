@props(['label' => null])

@php
    $label ??= __('ui.more_actions');
@endphp

{{-- The "More" menu. Rule: a header shows at most 1 primary and 2 secondary buttons; everything else
     goes here, destructive items last (the `danger` slot, after a divider, in the danger tone). Flux's
     menu gives arrow-key navigation, Esc to close and focus back on the trigger. Items are
     <flux:menu.item> (add variant="danger" to a destructive one). --}}
<flux:dropdown position="bottom" align="end" data-action-menu>
    <x-ui.button icon="ellipsis-horizontal" icon:trailing="chevron-down" {{ $attributes }}>{{ $label }}</x-ui.button>
    <flux:menu>
        {{ $slot }}
        @isset($danger)
            <flux:menu.separator />
            {{ $danger }}
        @endisset
    </flux:menu>
</flux:dropdown>
