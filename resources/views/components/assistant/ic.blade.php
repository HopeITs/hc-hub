{{--
    The assistant's line icons (24px grid, 2px stroke, currentColor). Decorative:
    the words next to each one carry the meaning.

      <x-assistant.ic name="check" />  — unknown names fall back to a dot.

    Stand-in for the shared <x-ic> of hc-hr's visual identity, same names and
    paths, until that component is in the hub; then the assistant views switch
    to <x-ic> and this file goes.
--}}
@props(['name' => 'dot'])

@php
    $paths = [
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'pen' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'alert' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>',
        'ban' => '<circle cx="12" cy="12" r="9"/><path d="m5.7 5.7 12.6 12.6"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'trash' => '<path d="M5 7h14M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'question' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6v.6M12 17v.5"/>',
        'file' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'dot' => '<circle cx="12" cy="12" r="4"/>',
    ];
    $fill = $name === 'dot' ? 'currentColor' : 'none';
@endphp

<svg {{ $attributes->merge(['class' => 'size-[1em] shrink-0']) }} viewBox="0 0 24 24" fill="{{ $fill }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" data-icon="{{ $name }}">{!! $paths[$name] ?? $paths['dot'] !!}</svg>
