{{--
    The strategy page's key figures, set like the list's (.dk-sfigs): big
    numbers split by hairlines, the label under each. Every count reads
    allPrograms(). Funding is drawn only when StrategyRollup returns it (money
    viewers); the plan figure only while the strategy runs this year.
--}}
@php
    $programsCount = $this->allPrograms->count();
    $projectsCount = (int) $this->allPrograms->sum('projects_count');
    $funding = $rollup['funding'];
    $plans = $this->currentYearPlans;
@endphp

<dl class="dk-sfigs" data-test="strategy-figures">
    <div>
        <dt>{{ __('strategies.fig_pillars') }}</dt>
        <dd data-test="strategy-pillars-count">{{ $strategy->pillars_count }}</dd>
    </div>

    <div>
        <dt>{{ __('common.programs') }}</dt>
        <dd><a href="{{ route('programs.index', ['strategy' => $strategy->id]) }}" wire:navigate data-test="strategy-programs-link">{{ $programsCount }}</a></dd>
    </div>

    <div>
        <dt>{{ __('common.projects') }}</dt>
        <dd><a href="{{ route('projects.index', ['strategy' => $strategy->id]) }}" wire:navigate data-test="strategy-projects-link">{{ $projectsCount }}</a></dd>
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
                        <bdi>{{ \App\Support\Desk\Money::compact($line['agreed'], $line['currency']) }}</bdi>
                    @endforeach
                </dd>
                @if ($funding['percent'] !== null)
                    <dd class="dk-sfig-sub">{{ __('strategies.percent_funded', ['percent' => $funding['percent']]) }}</dd>
                @endif
            @endif
        </div>
    @endif

    @if ($plans !== null)
        <div>
            <dt>{{ __('strategies.fig_have_plan', ['year' => $currentYear]) }}</dt>
            <dd><a href="{{ route('programs.index', ['strategy' => $strategy->id, 'gap' => 'no_plan']) }}" wire:navigate data-test="strategy-plans-link"><bdi>{{ $plans['planned'] }}/{{ $plans['programs'] }}</bdi></a></dd>
        </div>
    @endif
</dl>
