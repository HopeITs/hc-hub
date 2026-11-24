<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Journeys\OnboardingJourneys;

/*
 * "My first days": the new volunteer's own onboarding journey, from their own
 * account. The same steps HR follows (OnboardingJourneys::steps), worded for
 * the person they are about: what each step is, who does it, when, and the
 * one or two that ask something of them. It only ever reads the signed-in
 * user's own employee, and never shows HR's internal notes (why a step is
 * stuck is HR's to fix, not the volunteer's).
 */
new #[Layout('layouts::desk'), Title('hr_onboarding.first_days.title')] class extends Component {
    /** Shown to the volunteer: the record is HR's paperwork, not a step of theirs. */
    private const HIDDEN_STEPS = ['record'];

    public function mount(): void
    {
        abort_unless(Auth::user()?->employee_id !== null, 403);
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    #[Computed]
    public function steps(): \Illuminate\Support\Collection
    {
        $employee = Employee::query()->find(Auth::user()?->employee_id);
        abort_unless($employee !== null, 403);

        return collect(app(OnboardingJourneys::class)->steps($employee, $employee->onboardingJourney) ?? [])
            ->reject(fn (array $step): bool => in_array($step['key'], self::HIDDEN_STEPS, true))
            ->map(fn (array $step): array => $step + ['view' => $this->forVolunteer($step)])
            ->values();
    }

    /**
     * The step as the volunteer reads it: a status (icon + words), and when it
     * is theirs, where to do it.
     *
     * @param  array<string, mixed>  $step
     * @return array{icon: string, label: string, yours: bool, action: ?array{label: string, url: string}, date: mixed, date_label: ?string}
     */
    private function forVolunteer(array $step): array
    {
        $state = $step['state'];
        $yours = $state === OnboardingJourneys::STATE_TODO && $step['owner'] === 'volunteer';

        [$icon, $label] = match (true) {
            $state === OnboardingJourneys::STATE_DONE => ['check', __('hr_onboarding.first_days.state.done')],
            $yours => ['flag', __('hr_onboarding.first_days.state.yours')],
            $state === OnboardingJourneys::STATE_WAITING => ['info', __('hr_onboarding.first_days.state.after_contract')],
            // A step stuck on something (no items set up, no IT in the city...) is HR's to fix: the volunteer sees who follows it, not why.
            default => ['info', __('hr_onboarding.first_days.state.with_'.($step['owner'] === 'volunteer' ? 'hr' : $step['owner']))],
        };

        $action = $yours && in_array($step['key'], ['policies', 'guideline'], true)
            ? ['label' => __('hr_onboarding.first_days.action.'.$step['key']), 'url' => route('hr.onboarding.policies')]
            : null;

        // A session with a date set says when; anything else not done says by when it is expected.
        $scheduled = in_array($step['key'], ['orientation', 'manager_orientation'], true) ? ($step['data']['scheduled_for'] ?? null) : null;
        $isDone = $state === OnboardingJourneys::STATE_DONE;

        return [
            'icon' => $icon,
            'label' => $label,
            'yours' => $yours,
            'action' => $action,
            'date' => $isDone ? null : ($scheduled ?? $step['due']),
            'date_label' => $isDone ? null : ($scheduled ? __('hr_onboarding.first_days.scheduled') : ($step['due'] ? __('hr_onboarding.first_days.expected_by') : null)),
        ];
    }
};
?>

@php
    $steps = $this->steps;
    $journey = Employee::query()->find(Auth::user()->employee_id)?->onboardingJourney;
    $date = fn ($value) => $value?->timezone(config('app.timezone'))->translatedFormat('l d M Y');
    $total = $steps->count();
    $done = $steps->where('state', OnboardingJourneys::STATE_DONE)->count();
    $yours = $steps->filter(fn (array $step): bool => $step['view']['yours'])->values();
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<section class="mx-auto flex max-w-3xl flex-col gap-5" data-my-first-days>
    <x-ui.detail-header :breadcrumbs="[['label' => __('hr_onboarding.first_days.title')]]" :title="__('hr_onboarding.first_days.title')" :subtitle="__('hr_onboarding.first_days.lead')" />

    @if ($steps->isEmpty())
        <x-ui.panel><p class="p-4 text-sm {{ $muted }}">{{ __('hr_onboarding.first_days.none') }}</p></x-ui.panel>
    @else
        {{-- Where they stand, in one line. --}}
        <x-ui.panel>
            <div class="flex flex-wrap items-center justify-between gap-3 p-4" data-first-days-progress>
                <p class="font-semibold">
                    @if ($journey?->completed_at)
                        {{ __('hr_onboarding.first_days.complete') }}
                    @else
                        {{ __('hr_onboarding.first_days.progress', ['done' => $done, 'total' => $total]) }}
                    @endif
                </p>
                <span class="block h-2 w-40 overflow-hidden rounded-full bg-surface-2" aria-hidden="true"><span class="block h-full rounded-full bg-secondary-dark" style="inline-size: {{ $total > 0 ? (int) round($done / $total * 100) : 0 }}%"></span></span>
            </div>
        </x-ui.panel>

        @if ($yours->isNotEmpty())
            <section aria-labelledby="first-days-yours" class="rounded-lg border border-line border-s-4 border-s-status-critical bg-white p-4 dark:bg-surface-1" data-first-days-yours>
                <h2 id="first-days-yours" class="font-semibold">{{ trans_choice('hr_onboarding.first_days.yours_title', $yours->count(), ['count' => $yours->count()]) }}</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($yours as $step)
                        <li class="flex flex-wrap items-center justify-between gap-2" wire:key="yours-{{ $step['key'] }}">
                            <span>{{ __('hr_onboarding.first_days.step.'.$step['key']) }}</span>
                            @if ($step['view']['action'])
                                <x-ui.button tone="primary" size="sm" :href="$step['view']['action']['url']" wire:navigate>{{ $step['view']['action']['label'] }}</x-ui.button>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <x-ui.panel :title="__('hr_onboarding.first_days.steps_title')">
            <ol class="divide-y divide-secondary-soft px-4 dark:divide-line">
                @foreach ($steps as $index => $step)
                    @php($view = $step['view'])
                    <li class="grid gap-1 py-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:gap-3" data-step="{{ $step['key'] }}" wire:key="first-day-{{ $step['key'] }}">
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-2 font-medium">
                                <span class="tabular-nums {{ $muted }}" aria-hidden="true">{{ $index + 1 }}.</span>
                                <span>{{ __('hr_onboarding.first_days.step.'.$step['key']) }}</span>
                                @if ($step['late'])
                                    <span class="rounded-md bg-status-warn-soft px-1.5 py-0.5 text-xs font-semibold text-status-warn">{{ __('hr_onboarding.first_days.late') }}</span>
                                @endif
                            </p>
                            <p class="mt-0.5 text-sm {{ $muted }}">{{ __('hr_onboarding.first_days.about.'.$step['key']) }}</p>
                            @if ($view['date'])
                                <p class="mt-0.5 text-sm {{ $muted }}">{{ $view['date_label'] }}: <span class="tabular-nums">{{ $date($view['date']) }}</span></p>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-start gap-2 sm:justify-end">
                            <span class="inline-flex items-center gap-1.5 text-sm font-semibold">
                                <x-desk.icon :name="$view['icon']" />
                                {{ $view['label'] }}
                            </span>
                        </div>
                    </li>
                @endforeach
            </ol>
        </x-ui.panel>

        <p class="text-sm {{ $muted }}">{{ __('hr_onboarding.first_days.help') }}</p>
    @endif
</section>
