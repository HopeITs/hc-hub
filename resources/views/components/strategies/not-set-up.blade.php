@props(['strategyId', 'canLink' => false])

{{--
    A strategy with no programs linked has no targets, risks or reports to
    follow, so instead of a year line, a row of zeros and "Nothing needs your
    attention" (page review, 6 Nov 2026) it says, once, that it is not set up
    yet: with the Link programs action for whoever may link them, or who does
    it for everyone else. Used by the strategies list and the strategy page.

    strategyId: the strategy, for openProgramsModal().
    canLink: whether the viewer may link programs (LinksStrategyPrograms).
--}}
<div {{ $attributes->class('dk-snotset') }} data-test="strategy-not-set-up">
    <p><strong>{{ __('strategies.not_set_up') }}</strong> {{ __('strategies.no_programs_linked_here') }}</p>
    @if ($canLink)
        <button type="button" class="dk-btn" wire:click="openProgramsModal({{ $strategyId }})" data-test="strategy-empty-link-programs">{{ __('strategies.link_programs') }}</button>
    @else
        <p class="dk-snotset-who">{{ __('strategies.not_set_up_viewer') }}</p>
    @endif
</div>
