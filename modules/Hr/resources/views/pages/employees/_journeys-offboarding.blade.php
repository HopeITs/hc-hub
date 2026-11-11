{{--
    The exit journey of a departing volunteer: equipment, contract end,
    deactivation, access and card, plus the optional exit interview. Every
    step but the interview is read from its own record. Included by
    ⚡journeys.blade.php.
--}}
@php
    $journey = $this->offboardingJourney;
    $exitSteps = $offboarding['steps'];
    $calledOff = $offboarding['calledOff'];
    $required = collect($exitSteps)->whereIn('key', ['equipment', 'contract', 'deactivated', 'access']);
@endphp

<x-ui.panel :title="__('hr_journeys.offboarding.title')" data-journey="offboarding">
    <x-slot:toolbar>
        <span class="text-xs {{ $muted }}">
            @if ($journey?->completed_at)
                {{ __('hr_journeys.completed_on', ['date' => $date($journey->completed_at)]) }}
            @elseif ($calledOff)
                <span class="inline-flex items-center rounded-md bg-secondary-soft px-2 py-0.5 font-semibold text-secondary-dark dark:bg-surface-2 dark:text-ink">{{ __('hr_journeys.offboarding.called_off') }}</span>
            @else
                {{ __('hr_journeys.progress', ['done' => $required->where('state', 'done')->count(), 'total' => $required->count()]) }}
            @endif
        </span>
    </x-slot:toolbar>

    @if ($calledOff)
        <p class="px-4 py-3 text-sm {{ $muted }}">{{ __('hr_journeys.offboarding.called_off_help') }}</p>
    @else
        <ol class="divide-y divide-panel-border">
            @foreach ($exitSteps as $index => $step)
                @php
                    $key = $step['key'];
                    $isDone = $step['state'] === 'done';
                @endphp
                <li class="grid gap-2 px-4 py-3 sm:grid-cols-[minmax(0,1fr)_auto]" wire:key="offboarding-{{ $key }}" data-step="{{ $key }}">
                    <div class="min-w-0">
                        @include('hr::pages.employees._journeys-step-head', [
                            'label' => __('hr_journeys.offboarding.steps.'.$key),
                            'afterLabel' => $step['blocked_by'] ? __('hr_journeys.offboarding.steps.'.$step['blocked_by']) : null,
                            'dueLabel' => $key === 'contract' ? __('hr_journeys.offboarding.ends') : null,
                        ])

                        @if ($key === 'equipment' && ! $isDone)
                            <p class="mt-1 text-xs {{ $muted }}">
                                {{ trans_choice('hr_journeys.offboarding.equipment_outstanding', $step['data']['outstanding'], ['count' => $step['data']['outstanding']]) }}
                                @if ($step['data']['unrequested'] > 0)
                                    {{ trans_choice('hr_journeys.offboarding.equipment_unrequested', $step['data']['unrequested'], ['count' => $step['data']['unrequested']]) }}
                                @endif
                            </p>
                        @elseif ($key === 'deactivated')
                            @if ($step['note'])
                                <p class="mt-1 text-xs text-status-critical">{{ __('hr_journeys.offboarding.'.$step['note']) }}</p>
                            @elseif (! $isDone && $step['data']['deactivate_on'])
                                <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.offboarding.deactivation_help', ['date' => $date($step['data']['deactivate_on'])]) }}</p>
                            @endif
                        @elseif ($key === 'access' && $step['note'])
                            <p class="mt-1 text-xs text-status-critical">{{ __('hr_journeys.offboarding.'.$step['note']) }}</p>
                        @elseif ($key === 'interview')
                            <p class="mt-1 text-xs {{ $muted }}">
                                {{ $isDone
                                    ? __('hr_journeys.offboarding.interview_done_by', ['date' => $date($step['data']['held_at']), 'name' => $by($step['data']['held_by'])])
                                    : __('hr_journeys.offboarding.interview_help') }}
                            </p>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-start gap-2 sm:justify-end">
                        @if ($key === 'contract' && $step['data']['contract_id'] && $canViewContracts)
                            <x-ui.button tone="ghost" size="sm" icon="eye" :href="route('hr.contracts.show', $step['data']['contract_id'])" wire:navigate>{{ __('hr_journeys.offboarding.open_contract') }}</x-ui.button>
                        @elseif ($key === 'interview')
                            @if ($isDone)
                                <x-ui.button tone="ghost" size="sm" wire:click="askConfirm('reopen-offboarding', 'interview')">{{ __('hr_journeys.undo') }}</x-ui.button>
                            @else
                                <x-ui.button size="sm" icon="check" wire:click="markExitInterviewHeld" data-test="exit-interview-held">{{ __('hr_journeys.offboarding.interview_action') }}</x-ui.button>
                            @endif
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</x-ui.panel>
