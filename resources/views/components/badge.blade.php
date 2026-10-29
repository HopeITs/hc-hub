{{-- A tag (media-request .tag): neutral by default (.tag.soft = surface fill, text colour);
     `brand` is the red tag (.tag.act). Other colours are the legacy status pairs, which
     task 1.3 moves to media-request's neutral + icon style. --}}
@props(['color' => 'neutral'])
@php
    $tone = match ($color) {
        'neutral' => 'bg-neutral-200 text-neutral-800',
        'brand' => 'bg-primary text-white',
        default => "bg-{$color}-100 text-{$color}-800",
    };
@endphp
<span class="{{ $tone }} text-[13px] font-bold leading-normal me-2 px-[9px] py-0.5 rounded-inset ">{{$slot}}</span>
