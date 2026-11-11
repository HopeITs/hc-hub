@props(['results' => null])

{{--
    The one-sentence verdict on a strategy's targets (StrategyRollup::verdict()):
    the verdict word in bold, then how many of all its targets are on pace, how
    many are falling behind and how many have no figures yet, on a soft tint of
    its tone. No rated targets: a neutral sentence. Renders nothing when the
    viewer may not see results (the rollup's `results` is null).

    results: the rollup's `results` array ({on, total, unrated, …}) or null.
--}}
@if ($results !== null)
    @php
        $verdict = \Modules\Tracking\Support\StrategyRollup::verdict((int) $results['on'], (int) $results['total'], (int) ($results['unrated'] ?? 0));
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
                'incomplete' => __('strategies.verdict_incomplete'),
                'mostly_on_track' => __('strategies.verdict_mostly_on_track'),
                default => __('strategies.verdict_off_track'),
            } }}</strong>
            <span>
                {!! trans_choice('strategies.verdict_on_pace', $verdict['all'], ['on' => '<bdi>'.$verdict['on'].'</bdi>', 'total' => '<bdi>'.$verdict['all'].'</bdi>']) !!}
                @if ($verdict['behind'] > 0)
                    {!! trans_choice('strategies.verdict_behind', $verdict['behind'], ['count' => '<bdi>'.$verdict['behind'].'</bdi>']) !!}
                @endif
                @if ($verdict['unrated'] > 0)
                    {!! trans_choice('strategies.verdict_unrated', $verdict['unrated'], ['count' => '<bdi>'.$verdict['unrated'].'</bdi>']) !!}
                @endif
            </span>
        @endif
    </p>
@endif
