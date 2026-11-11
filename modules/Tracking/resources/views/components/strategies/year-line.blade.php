@props([
    'start',
    'end',
    'current',
    'tone' => null,
])

{{--
    A strategy's years as a dot-thread (.dk-thread): one dot per year, the
    years already lived filled in, the current year ringed in the verdict's
    tone and labelled "Now" in words, the year under every dot.

    start, end, current: years (int). tone: 'good' | 'warn' | 'critical' | null
    (the verdict tone, StrategyRollup::verdict()).
--}}
@php
    $currentYear = (int) $current;
    $nowClass = match ($tone) {
        'good' => 'dk-now dk-good',
        'warn' => 'dk-now dk-warn',
        'critical' => 'dk-now dk-bad',
        default => 'dk-now',
    };
@endphp

<ol {{ $attributes->class('dk-thread dk-yl') }} aria-label="{{ __('strategies.year_line_label', ['start' => $start, 'end' => $end]) }}" data-year-line>
    @for ($year = (int) $start; $year <= (int) $end; $year++)
        @if ($year === $currentYear)
            <li class="{{ $nowClass }}" aria-current="step">
                <i aria-hidden="true"></i>
                <span class="dk-yl-now">{{ __('strategies.now') }}</span>
                <span class="dk-yl-y dk-num">{{ $year }}</span>
            </li>
        @else
            <li @if ($year < $currentYear) class="dk-done" @endif>
                <i aria-hidden="true"></i>
                <span class="dk-yl-y dk-num">{{ $year }}</span>
            </li>
        @endif
    @endfor
</ol>
