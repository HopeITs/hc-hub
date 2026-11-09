@props(['name'])

@php
    /* The Tracking Desk icon set (App\Support\Desk\Icons). Unknown names render nothing rather than a broken glyph. */
    $paths = \App\Support\Desk\Icons::Paths;
@endphp

@isset($paths[$name])
    <svg {{ $attributes->class('dk-i') }} viewBox="0 0 24 24" aria-hidden="true">{!! $paths[$name] !!}</svg>
@endisset
