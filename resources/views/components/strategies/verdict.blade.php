@props(['results' => null])

{{--
    The one-sentence verdict on a strategy's targets (StrategyRollup::verdict()):
    the verdict word in bold, then how many targets are on pace and how many
    are falling behind, on a soft tint of its tone. No rated targets: a
    neutral sentence. Renders nothing when the viewer may not see results
    (the rollup's `results` is null).

    results: the rollup's `results` array ({on, total, …}) or null.
--}}
@if ($results !== null)
    @php
        $verdict = \App\Support\StrategyRollup::verdict((int) $results['on'], (int) $results['total']);
        $toneClass = match ($verdict['tone'] ?? null) {
            'good' => 'dk-good',
            'warn' => 'dk-warn',
            'critical' => 'dk-bad',
            default => 'dk-mute',
        };
    @endphp

    <p {{ $attributes->class(['dk-verdict', $toneClass]) }} @if ($verdict !== null) data-verdict="{{ $verdict['key'] }}" @endif data-test="strategy-verdict">
        @if ($verdict === null)
            <span>{{ __('strategies.verdict_none') }}</span>
        @else
            <strong>{{ match ($verdict['key']) {
                'on_track' => __('strategies.verdict_on_track'),
                'mostly_on_track' => __('strategies.verdict_mostly_on_track'),
                default => __('strategies.verdict_off_track'),
            } }}</strong>
            <span>
                {!! trans_choice('strategies.verdict_on_pace', $verdict['total'], ['on' => '<bdi>'.$verdict['on'].'</bdi>', 'total' => '<bdi>'.$verdict['total'].'</bdi>']) !!}
                @if ($verdict['behind'] > 0)
                    {!! trans_choice('strategies.verdict_behind', $verdict['behind'], ['count' => '<bdi>'.$verdict['behind'].'</bdi>']) !!}
                @endif
            </span>
        @endif
    </p>
@endif
