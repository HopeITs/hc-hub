{{--
    The assistant's buttons.

      <x-assistant.button wire:click="confirmDraft('…')">…</x-assistant.button>
      <x-assistant.button variant="outline" size="sm" :href="$url">…</x-assistant.button>

    variant: primary (red fill — the one main action) | outline (card + ink border)
             | quiet (surface fill) | link (underlined text)
    size:    sm | md (default); 44px minimum on touch screens at every size
    href:    renders a link (wire:navigate)
    icon:    optional leading <x-assistant.ic> name
    loading: true (default) — while Livewire runs this button's action it is
             disabled and a spinner replaces the icon. target: the wire:target
             to watch; defaults to the method in wire:click.

    Stand-in for the shared <x-button> of hc-hr's visual identity (same props),
    until that component is in the hub; then the assistant views switch to
    <x-button> and this file goes.
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'icon' => null,
    'loading' => true,
    'target' => null,
])

@php
    $variant = in_array($variant, ['primary', 'outline', 'quiet', 'link'], true) ? $variant : 'primary';

    $classes = collect([
        'inline-flex min-h-11 items-center justify-center gap-2 rounded-control font-bold transition duration-150 ease-in-out',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-800',
        'disabled:cursor-not-allowed disabled:opacity-45',
        $size === 'sm' ? 'px-3 py-1.5 text-small' : 'px-[18px] py-2 text-body',
        match ($variant) {
            'primary' => 'border-[1.5px] border-transparent bg-primary text-white hover:bg-primary-hover active:scale-[0.98]',
            'outline' => 'border-[1.5px] border-ink bg-card text-ink hover:bg-hover active:scale-[0.98]',
            'quiet' => 'border-[1.5px] border-transparent bg-surface text-ink hover:bg-hover active:scale-[0.98]',
            'link' => 'px-1 text-ink underline underline-offset-2 hover:text-ink-2',
        },
    ])->implode(' ');

    $click = $attributes->wire('click')->value();
    $target ??= $click ? (string) str($click)->before('(')->trim() : null;
    $spinner = 'inline-block size-4 shrink-0 animate-spin rounded-full border-2 border-current border-e-transparent';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes, 'wire:navigate' => true]) }}>
        @if ($icon)<x-assistant.ic :name="$icon" />@endif
        {{ $slot }}
    </a>
@else
    <button
        @if ($loading)
            wire:loading.attr="disabled"
            @if ($target) wire:target="{{ $target }}" @endif
        @endif
        {{ $attributes->merge(['class' => $classes, 'type' => $type]) }}
    >
        @if ($loading)
            <span class="{{ $spinner }}" wire:loading @if ($target) wire:target="{{ $target }}" @endif aria-hidden="true"></span>
            <span class="sr-only" wire:loading @if ($target) wire:target="{{ $target }}" @endif role="status">{{ __('general.a11y.loading') }}</span>
            @if ($icon)<x-assistant.ic :name="$icon" wire:loading.remove :wire:target="$target" />@endif
        @elseif ($icon)
            <x-assistant.ic :name="$icon" />
        @endif
        {{ $slot }}
    </button>
@endif
