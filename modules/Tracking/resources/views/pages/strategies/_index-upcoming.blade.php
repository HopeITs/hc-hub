{{--
    Upcoming strategies: quiet rows with the years, how soon each starts and
    how many of its programs already have a plan for its first year.
--}}
@php
    use Illuminate\Support\Carbon;

    $readiness = $this->readiness;
    $today = Carbon::today();
@endphp

<section class="dk-ssec" aria-labelledby="upcoming-strategies-heading" data-test="upcoming-strategies">
    <h2 class="dk-ssec-h" id="upcoming-strategies-heading">{{ __('strategies.upcoming_strategies') }}</h2>

    <ul class="dk-slist">
        @foreach ($portfolio['upcoming'] as $strategy)
            @php
                $ready = $readiness[$strategy['id']] ?? null;
                $state = __('strategies.starts_on', ['date' => Carbon::create($strategy['start_year'], 1, 1)->translatedFormat(__('formats.month_year'))]);

                if ($strategy['programs_count'] === 0) {
                    $state .= ' · '.__('strategies.no_programs_linked_yet');
                } elseif ($ready !== null) {
                    $state .= ' · '.trans_choice('strategies.programs_ready', $ready['programs'], ['planned' => $ready['planned'], 'total' => $ready['programs']]);
                }
            @endphp
            <li wire:key="upcoming-strategy-{{ $strategy['id'] }}" data-test="upcoming-strategy-{{ $strategy['id'] }}">
                <div class="dk-slist-main">
                    <strong>{{ $strategy['name'] }}</strong>
                    <span class="dk-slist-years dk-num"><bdi>{{ $strategy['start_year'] }}–{{ $strategy['end_year'] }}</bdi></span>
                </div>
                <p class="dk-slist-state">{{ $state }}</p>
                <div class="dk-slist-acts">
                    <a href="{{ $strategy['url'] }}" wire:navigate class="dk-slink" aria-label="{{ __('strategies.open_named', ['name' => $strategy['name']]) }}">{{ __('strategies.open') }}<x-desk.icon name="arrow" class="dk-flip" /></a>
                </div>
            </li>
        @endforeach
    </ul>
</section>
