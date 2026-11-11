@props([
    'items' => [],
    'heading' => null,
])

{{--
    "Needs your attention": one plain sentence per item, each with one
    labelled link to the register already narrowed to it. With nothing to
    show, one calm sentence says so.

    items: list<array{text: string|Htmlable, label: string, href: string, tone: 'critical'|'warn'}>
           (Modules\Tracking\Support\StrategyAttention::items() builds them for a strategy).
--}}
<section {{ $attributes->class('dk-attn') }} data-test="strategy-attention">
    <h3 class="dk-attn-h">{{ $heading ?? __('strategies.needs_your_attention') }}</h3>

    @if ($items === [])
        <p class="dk-attn-none">{{ __('strategies.nothing_needs_attention') }}</p>
    @else
        <ul>
            @foreach ($items as $item)
                <li @class(['dk-attn-i', 'dk-bad' => $item['tone'] === 'critical', 'dk-warn' => $item['tone'] !== 'critical'])>
                    <span class="dk-attn-t">{{ $item['text'] }}</span>
                    <a href="{{ $item['href'] }}" wire:navigate class="dk-attn-a">{{ $item['label'] }}<x-desk.icon name="arrow" class="dk-flip" /></a>
                </li>
            @endforeach
        </ul>
    @endif
</section>
