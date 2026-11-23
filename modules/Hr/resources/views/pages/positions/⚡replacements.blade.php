<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\CriticalPositions\CandidateAiRanker;
use Modules\Hr\Support\CriticalPositions\CriticalPositionAlerts;
use Modules\Hr\Support\CriticalPositions\InternalCandidateRanker;
use Modules\Hr\Support\CriticalPositions\PastApplicantsSource;

/*
 * Suggested replacements for one critical position. Suggestions only: nothing
 * here promotes anyone or creates a contract, and the panel is closed to
 * direct managers and to the candidates themselves
 * (PositionPolicy::viewReplacements).
 */
new #[Layout('layouts::desk'), Title('hr_positions.critical.panel_title')] class extends Component {
    #[Locked]
    public int $positionId;

    public function mount(Position $position): void
    {
        Gate::authorize('viewReplacements', $position);

        $this->positionId = $position->id;
    }

    #[Computed]
    public function position(): Position
    {
        $position = Position::query()->findOrFail($this->positionId);

        Gate::authorize('viewReplacements', $position);

        return $position;
    }

    /** @return list<\Modules\Hr\Support\CriticalPositions\CandidateSuggestion> */
    #[Computed]
    public function internal(): array
    {
        $suggestions = app(InternalCandidateRanker::class)->suggest($this->position, Auth::user(), $this->limit());

        return config('critical-positions.ai_ranking.enabled') ? app(CandidateAiRanker::class)->rank($this->position, $suggestions) : $suggestions;
    }

    /** @return list<\Modules\Hr\Support\CriticalPositions\CandidateSuggestion> */
    #[Computed]
    public function cvs(): array
    {
        return app(PastApplicantsSource::class)->suggest($this->position, Auth::user(), $this->limit());
    }

    private function limit(): int
    {
        return (int) config('critical-positions.suggestions.limit', 10);
    }
};
?>

@php
    $position = $this->position;
    $alerts = app(CriticalPositionAlerts::class);
    $reasons = $alerts->reasonsFor($position);
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<section class="flex flex-col gap-5" data-replacements-panel>
    <x-ui.detail-header
        :breadcrumbs="[
            ['label' => __('hr_positions.critical.title'), 'url' => route('hr.positions.critical')],
            ['label' => (string) $position->title],
        ]"
        :title="__('hr_positions.critical.panel_title')"
        :subtitle="(string) $position->title"
    />

    @foreach ($reasons as $reason)
        <p class="text-sm {{ $muted }}" data-replacement-reason>{{ __('hr_positions.critical.reason.'.$reason) }}</p>
    @endforeach

    <p class="text-sm {{ $muted }}">{{ __('hr_positions.critical.suggestions_only') }}</p>

    <div class="grid gap-5 lg:grid-cols-2">
        <x-ui.panel :title="__('hr_positions.critical.internal_title')" data-replacements-internal>
            @forelse ($this->internal as $suggestion)
                <div class="grid gap-1 border-b border-secondary-soft px-4 py-3 last:border-b-0 dark:border-line" wire:key="internal-{{ $suggestion->id }}">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="font-semibold" dir="auto">{{ $suggestion->name }}</p>
                            <p class="text-xs {{ $muted }}" dir="auto">{{ $suggestion->detail }}</p>
                        </div>
                        <span class="text-sm font-semibold tabular-nums">{{ __('hr_positions.critical.score', ['score' => $suggestion->score]) }}</span>
                    </div>
                    <ul class="grid gap-0.5 text-xs {{ $muted }}">
                        @foreach ($suggestion->reasonTexts() as $text)
                            <li>{{ $text }}</li>
                        @endforeach
                    </ul>
                    @if ($suggestion->url)
                        <div><x-ui.button size="sm" tone="ghost" :href="$suggestion->url" wire:navigate>{{ __('hr_positions.critical.open_profile') }}</x-ui.button></div>
                    @endif
                </div>
            @empty
                <x-ui.empty-state icon="users" :heading="__('hr_positions.critical.internal_empty')" :body="__('hr_positions.critical.internal_empty_help')" />
            @endforelse
        </x-ui.panel>

        <x-ui.panel :title="__('hr_positions.critical.cv_title')" data-replacements-cvs>
            @forelse ($this->cvs as $suggestion)
                <div class="grid gap-1 border-b border-secondary-soft px-4 py-3 last:border-b-0 dark:border-line" wire:key="cv-{{ $suggestion->id }}">
                    <p class="font-semibold" dir="auto">{{ $suggestion->name }}</p>
                    <ul class="grid gap-0.5 text-xs {{ $muted }}">
                        @foreach ($suggestion->reasonTexts() as $text)
                            <li>{{ $text }}</li>
                        @endforeach
                    </ul>
                    @if ($suggestion->url)
                        <div><x-ui.button size="sm" tone="ghost" :href="$suggestion->url" wire:navigate>{{ __('hr_positions.critical.open_candidate') }}</x-ui.button></div>
                    @endif
                </div>
            @empty
                <x-ui.empty-state icon="document-text" :heading="__('hr_positions.critical.cv_empty')" :body="__('hr_positions.critical.cv_empty_help')" />
            @endforelse
        </x-ui.panel>
    </div>
</section>
