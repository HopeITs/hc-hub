{{--
    Past strategies: a quiet list, most recently ended first, 15 per page
    (pageName strategiesPage). A strategy left without programs offers to link
    them, and, when it has no pillars either, to delete it.
--}}
@php
    use Illuminate\Support\Carbon;
@endphp

<section class="dk-ssec" aria-labelledby="past-strategies-heading" data-test="past-strategies">
    <h2 class="dk-ssec-h" id="past-strategies-heading">{{ __('strategies.past_strategies') }}</h2>

    <ul class="dk-slist">
        @foreach ($portfolio['past'] as $strategy)
            @php
                $ended = Carbon::create($strategy['end_year'], 12)->translatedFormat(__('formats.month_year'));
            @endphp
            <li wire:key="past-strategy-{{ $strategy['id'] }}" data-test="past-strategy-{{ $strategy['id'] }}">
                <div class="dk-slist-main">
                    <strong>{{ $strategy['name'] }}</strong>
                    <span class="dk-slist-years dk-num"><bdi>{{ $strategy['start_year'] }}–{{ $strategy['end_year'] }}</bdi></span>
                </div>
                <p class="dk-slist-state">
                    @if ($strategy['programs_count'] > 0)
                        {{ trans_choice('strategies.count_program', $strategy['programs_count'], ['count' => $strategy['programs_count']]) }} · {{ __('strategies.ended_on', ['date' => $ended]) }}
                    @else
                        {{ __('strategies.no_programs_linked') }}
                    @endif
                </p>
                <div class="dk-slist-acts">
                    @if ($strategy['programs_count'] === 0 && $this->canLinkPrograms)
                        <button type="button" wire:click="openProgramsModal({{ $strategy['id'] }})" class="dk-slink" aria-label="{{ __('strategies.link_programs_to', ['name' => $strategy['name']]) }}" data-test="strategy-link-programs">{{ __('strategies.link_programs') }}</button>
                    @endif
                    @if ($strategy['programs_count'] === 0 && $strategy['pillars_count'] === 0 && $this->canDeleteStrategies)
                        <button type="button" wire:click="confirmDelete({{ $strategy['id'] }})" class="dk-slink dk-danger" aria-label="{{ __('common.delete_name', ['name' => $strategy['name']]) }}" data-test="delete-strategy-button">{{ __('common.delete') }}</button>
                    @endif
                    <a href="{{ $strategy['url'] }}" wire:navigate class="dk-slink" aria-label="{{ __('strategies.open_named', ['name' => $strategy['name']]) }}">{{ __('strategies.open') }}<x-desk.icon name="arrow" class="dk-flip" /></a>
                </div>
            </li>
        @endforeach
    </ul>

    <flux:pagination :paginator="$portfolio['past']" class="text-secondary-medium! dark:text-ink-muted!" />
</section>
