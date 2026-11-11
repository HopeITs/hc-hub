@props([
    'card',
    'layout' => 'list',
    'figures' => [],
    'pg',
    'fit' => '',
    'empty' => '',
])

@php
    $tones = ['good' => 'dk-good', 'warn' => 'dk-warn', 'critical' => 'dk-bad', 'active' => 'dk-act', 'neutral' => 'dk-mute'];
@endphp

{{--
    The body of an HR Desk card: its headline figures, then its rows in the
    card's own shape (the Desk's page-card shapes: agenda, people, meter, rank,
    feed, tagged). A row with nowhere to lead is plain text, not a link.
--}}
<div class="dk-card" data-card-body data-card-layout="{{ $layout }}">
    @if ($figures !== [])
        <dl class="dk-card-figs" data-card-figures>
            @foreach ($figures as $figure)
                <div class="dk-card-fig" data-tone="{{ $figure['tone'] }}">
                    <dt>{{ $figure['label'] }}</dt>
                    <dd class="dk-num" @if ($figure['clock'] ?? false) data-desk-clock @endif>{{ $figure['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    @endif

    @if ($pg['total'] > 0)
        <ul class="dk-card-rows" {{ $fit }} data-card-rows data-layout="{{ $layout }}">
            @foreach ($pg['slice'] as $row)
                @php
                    $tag = $row['href'] !== '' ? 'a' : 'div';
                @endphp
                <li wire:key="{{ $card }}-{{ $row['key'] }}">
                    <{{ $tag }} @if ($row['href'] !== '') href="{{ $row['href'] }}" wire:navigate @endif class="dk-card-row" title="{{ $row['title'] }}" data-tone="{{ $row['badge']['tone'] ?? 'neutral' }}">
                        @if ($row['lead'] !== null)
                            <span class="dk-card-l" data-kind="{{ $row['lead']['kind'] }}" data-tone="{{ $row['lead']['tone'] }}" aria-hidden="true">
                                @if ($row['lead']['big'] !== '')<b>{{ $row['lead']['big'] }}</b>@endif
                                @if ($row['lead']['small'] !== '')<small>{{ $row['lead']['small'] }}</small>@endif
                            </span>
                        @endif
                        <span class="dk-card-t">{{ $row['title'] }}</span>
                        @if ($row['meta'] !== '')
                            <span class="dk-card-m">{{ $row['meta'] }}</span>
                        @endif
                        @if ($row['bar'] !== null)
                            <span class="dk-card-r" data-tone="{{ $row['bar']['tone'] }}" style="--from: {{ $row['bar']['from'] }}%; --to: {{ $row['bar']['to'] }}%;" aria-hidden="true">@if ($row['bar']['to'] > $row['bar']['from'])<i></i>@endif</span>
                        @endif
                        @if ($row['end'] !== null)
                            <span class="dk-card-e"><b class="dk-num">{{ $row['end']['big'] }}</b>@if ($row['end']['small'] !== '')<small>{{ $row['end']['small'] }}</small>@endif</span>
                        @endif
                        @if ($row['badge'] !== null && $row['badge']['label'] !== '')
                            <span class="dk-pill {{ $tones[$row['badge']['tone']] ?? 'dk-mute' }} dk-card-b">{{ $row['badge']['label'] }}</span>
                        @endif
                    </{{ $tag }}>
                </li>
            @endforeach
        </ul>
    @else
        <div class="dk-card-empty" data-card-empty>
            <p>{{ $empty }}</p>
        </div>
    @endif
</div>
