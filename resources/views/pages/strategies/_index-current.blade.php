{{--
    Current strategies: one typographic block per active strategy (name, the
    year line, the verdict, four figures, what needs attention). A figure the
    viewer may not see (null in StrategyRollup) is never drawn. A strategy with
    no programs linked shows x-strategies.not-set-up instead of all of that. With no active
    strategy, one sentence says so and offers to start the next one.
--}}
@php
    use App\Support\Desk\Money;
    use App\Support\StrategyAttention;
    use App\Support\StrategyRollup;
    use Illuminate\Support\Carbon;

    $rollups = $this->rollups;
    $readiness = $this->readiness;
    $currentYear = $portfolio['current_year'];
@endphp

<section class="dk-ssec" aria-labelledby="current-strategies-heading" data-test="current-strategies">
    <h2 class="dk-ssec-h" id="current-strategies-heading">{{ __('strategies.current_strategies') }}</h2>

    @if ($portfolio['current']->isEmpty())
        <div class="dk-snone" data-test="no-active-strategy">
            <p>
                {{ __('strategies.no_active_strategy') }}
                @if ($portfolio['last_ended_year'] !== null)
                    {!! __('strategies.last_one_ended', ['date' => '<bdi>'.e(Carbon::create($portfolio['last_ended_year'], 12)->translatedFormat(__('formats.month_year'))).'</bdi>']) !!}
                @endif
            </p>
            @if ($this->canCreateStrategies)
                <x-ui.button tone="primary" icon="plus" wire:click="openCreateModal" data-test="start-next-strategy">{{ __('strategies.start_next_strategy') }}</x-ui.button>
            @endif
        </div>
    @endif

    @foreach ($portfolio['current'] as $strategy)
        @php
            $rollup = $rollups[$strategy['id']] ?? null;
            $results = $rollup['results'] ?? null;
            $verdict = $results === null ? null : StrategyRollup::verdict($results['on'], $results['total']);
            $funding = $rollup['funding'] ?? null;
            $risks = $rollup['risks'] ?? null;
            $lateReports = $rollup['late']['reports'] ?? null;
            $strategyReadiness = $readiness[$strategy['id']] ?? null;
            $attention = $rollup === null ? [] : StrategyAttention::items($strategy['id'], $rollup, $strategyReadiness, $currentYear);
            $showsAttention = $rollup !== null && ($rollup['late'] !== null || $risks !== null || $strategyReadiness !== null);
            $yearOfSpan = $currentYear - $strategy['start_year'] + 1;
            $notSetUp = $strategy['programs_count'] === 0;
        @endphp

        <article
            class="dk-cstrat cursor-pointer"
            wire:key="current-strategy-{{ $strategy['id'] }}"
            x-on:click="if (! $event.target.closest('a, button, input')) Livewire.navigate(@js($strategy['url']))"
            aria-labelledby="current-strategy-{{ $strategy['id'] }}-name"
            data-test="current-strategy-{{ $strategy['id'] }}"
        >
            <header class="dk-cstrat-h">
                <div class="min-w-0">
                    <h2 class="dk-cstrat-name" id="current-strategy-{{ $strategy['id'] }}-name">{{ $strategy['name'] }}</h2>
                    <p class="dk-cstrat-meta">
                        {!! __('strategies.year_of_total_ends', [
                            'current' => '<bdi>'.$yearOfSpan.'</bdi>',
                            'total' => '<bdi>'.$strategy['duration_years'].'</bdi>',
                            'date' => '<bdi>'.e(Carbon::create($strategy['end_year'], 12)->translatedFormat(__('formats.month_year'))).'</bdi>',
                        ]) !!}
                    </p>
                </div>
                <a href="{{ $strategy['url'] }}" wire:navigate class="dk-btn" data-test="open-strategy-{{ $strategy['id'] }}">
                    {{ __('strategies.open_strategy') }}<x-desk.icon name="arrow" class="dk-flip" />
                </a>
            </header>

            @if ($notSetUp)
                <x-strategies.not-set-up :strategy-id="$strategy['id']" :can-link="$this->canLinkPrograms" />
            @else
            <x-strategies.year-line :start="$strategy['start_year']" :end="$strategy['end_year']" :current="$currentYear" :tone="$verdict['tone'] ?? null" />

            <x-strategies.verdict :results="$results" />

            <dl class="dk-sfigs">
                <div>
                    <dt>{{ __('common.programs') }}</dt>
                    <dd><a href="{{ route('programs.index', ['strategy' => $strategy['id']]) }}" wire:navigate aria-label="{{ trans_choice('strategies.programs_link_label', $strategy['programs_count'], ['count' => $strategy['programs_count']]) }}" data-test="strategy-programs-link">{{ $strategy['programs_count'] }}</a></dd>
                </div>

                @if ($funding !== null)
                    <div data-test="strategy-funding">
                        <dt>{{ __('strategies.col_funding') }}</dt>
                        @if ($funding['lines'] === [])
                            <dd class="dk-muted">—</dd>
                            <dd class="dk-sfig-sub">{{ __('strategies.no_funding_linked') }}</dd>
                        @else
                            <dd class="dk-sfig-money">
                                @foreach ($funding['lines'] as $line)
                                    @if (! $loop->first)
                                        <span class="dk-sfig-sep" aria-hidden="true">·</span>
                                    @endif
                                    <bdi>{{ Money::compact($line['agreed'], $line['currency']) }}</bdi>
                                @endforeach
                            </dd>
                            @if ($funding['percent'] !== null)
                                <dd class="dk-sfig-sub">{{ __('strategies.percent_funded', ['percent' => $funding['percent']]) }}</dd>
                            @endif
                        @endif
                    </div>
                @endif

                @if ($risks !== null)
                    <div>
                        <dt>{{ __('strategies.col_high_risks') }}</dt>
                        <dd @class(['text-status-critical' => $risks > 0]) data-test="strategy-high-risks">{{ $risks }}</dd>
                    </div>
                @endif

                @if ($lateReports !== null)
                    <div>
                        <dt>{{ __('strategies.late_reports') }}</dt>
                        <dd @class(['text-status-critical' => $lateReports > 0]) data-test="strategy-late-reports">{{ $lateReports }}</dd>
                    </div>
                @endif
            </dl>

            @if ($showsAttention)
                <x-strategies.attention-list :items="$attention" />
            @endif
            @endif
        </article>
    @endforeach
</section>
