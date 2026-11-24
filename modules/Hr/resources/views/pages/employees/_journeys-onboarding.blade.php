{{--
    The onboarding journey of a new volunteer: record, first contract, then,
    side by side, login account, electronics, ID card, HR's session, the
    manager's session, policies and the guideline. The Mug sits below the
    steps: it does not hold the journey back. Included by ⚡journeys.blade.php.
--}}
@php
    $journey = $this->onboardingJourney;
    $doneCount = collect($onboardingSteps)->where('state', 'done')->count();
    $mug = $journey !== null ? app(\Modules\Hr\Support\Journeys\OnboardingJourneys::class)->mug($this->employee) : null;
@endphp

<x-ui.panel :title="__('hr_journeys.onboarding.title')" data-journey="onboarding">
    <x-slot:toolbar>
        <span class="text-xs {{ $muted }}">
            @if ($journey?->completed_at)
                {{ __('hr_journeys.completed_on', ['date' => $date($journey->completed_at)]) }}
            @else
                {{ __('hr_journeys.progress', ['done' => $doneCount, 'total' => count($onboardingSteps)]) }}
            @endif
        </span>
    </x-slot:toolbar>

    <ol class="divide-y divide-panel-border">
        @foreach ($onboardingSteps as $index => $step)
            @php
                $key = $step['key'];
                $isDone = $step['state'] === 'done';
            @endphp
            <li class="grid gap-2 px-4 py-3 sm:grid-cols-[minmax(0,1fr)_auto]" wire:key="onboarding-{{ $key }}" data-step="{{ $key }}">
                <div class="min-w-0">
                    @include('hr::pages.employees._journeys-step-head', [
                        'label' => __('hr_journeys.onboarding.steps.'.$key),
                        'afterLabel' => $step['blocked_by'] ? __('hr_journeys.onboarding.steps.'.$step['blocked_by']) : null,
                    ])

                    @if ($key === 'account' && $step['note'])
                        <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.onboarding.account-missing') }}</p>
                    @elseif (in_array($key, ['electronics', 'other_items'], true))
                        @if ($step['note'])
                            <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.onboarding.'.$step['note']) }}</p>
                        @endif
                        @if ($step['data']['missing']->isNotEmpty())
                            <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.onboarding.missing_items') }}: {{ $step['data']['missing']->map(fn ($item) => $item['deliverer'] ? __('hr_journeys.onboarding.item_by', ['item' => $item['name'], 'name' => $item['deliverer']->name]) : __('hr_journeys.onboarding.item_nobody', ['item' => $item['name']]))->implode(', ') }}</p>
                        @endif
                    @elseif ($key === 'manager_orientation')
                        <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.onboarding.manager_help') }}</p>
                        <p class="mt-1 text-xs {{ $muted }}">{{ $step['data']['managers']->isEmpty() ? __('hr_journeys.onboarding.manager_none') : __('hr_journeys.onboarding.manager_names', ['names' => $step['data']['managers']->map(fn ($manager) => $manager->name)->implode(', ')]) }}</p>
                        @if ($isDone)
                            <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.onboarding.orientation_done_by', ['date' => $date($step['data']['scheduled_for']), 'name' => $by($step['data']['completed_by'])]) }}</p>
                        @elseif ($step['data']['scheduled_for'])
                            <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.onboarding.orientation_on', ['date' => $date($step['data']['scheduled_for'])]) }}</p>
                        @endif
                    @elseif (in_array($key, ['policies', 'guideline'], true))
                        @if ($step['note'])
                            <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.onboarding.'.$step['note']) }}</p>
                        @elseif (! $isDone && $step['data']['pending']->isNotEmpty())
                            <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.onboarding.policies_pending', ['count' => $step['data']['pending']->count(), 'total' => $step['data']['required']]) }}</p>
                        @endif
                    @elseif ($key === 'card')
                        @if ($isDone)
                            <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.onboarding.card_done_by', ['date' => $date($step['data']['handed_over_at']), 'name' => $by($step['data']['handed_over_by'])]) }}</p>
                        @elseif ($step['note'] === 'card-items-missing')
                            <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.onboarding.card-items-missing') }}</p>
                        @elseif ($step['note'])
                            <p class="mt-1 text-xs {{ $muted }}">{{ __($step['note']) }}</p>
                        @elseif ($step['data']['digital_ready'])
                            <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.onboarding.card_help') }}</p>
                        @endif
                    @elseif ($key === 'orientation')
                        @if ($isDone)
                            <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.onboarding.orientation_done_by', ['date' => $date($step['data']['scheduled_for']), 'name' => $by($step['data']['completed_by'])]) }}</p>
                        @elseif ($step['data']['scheduled_for'])
                            <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.onboarding.orientation_on', ['date' => $date($step['data']['scheduled_for'])]) }}</p>
                        @endif
                    @endif
                </div>

                <div class="flex flex-wrap items-start gap-2 sm:justify-end">
                    @if ($key === 'electronics' && $journey !== null && $step['note'] === 'electronics-choose-it')
                        <x-ui.form-select wire:change="chooseElectronicsDeliverer($event.target.value)" :label="__('hr_journeys.onboarding.choose_deliverer')" :placeholder="__('common.select')">
                            @foreach ($step['data']['candidates'] as $candidate)
                                <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                            @endforeach
                        </x-ui.form-select>
                    @elseif ($key === 'manager_orientation' && $journey !== null)
                        @if ($isDone)
                            <x-ui.button tone="ghost" size="sm" wire:click="askConfirm('reopen-onboarding', 'manager_orientation')">{{ __('hr_journeys.undo') }}</x-ui.button>
                        @else
                            <form wire:submit="scheduleManagerOrientation" class="flex flex-wrap items-end gap-2">
                                <flux:input type="date" size="sm" wire:model="managerDate" :label="__('hr_journeys.onboarding.orientation_date')" />
                                <x-ui.button type="submit" size="sm">{{ $step['data']['scheduled_for'] ? __('hr_journeys.onboarding.reschedule') : __('hr_journeys.onboarding.schedule') }}</x-ui.button>
                                @if ($step['data']['can_complete'])
                                    <x-ui.button type="button" tone="primary" size="sm" icon="check" wire:click="completeManagerOrientation" data-test="manager-orientation-done">{{ __('hr_journeys.onboarding.orientation_action') }}</x-ui.button>
                                @endif
                            </form>
                        @endif
                    @elseif (in_array($key, ['policies', 'guideline'], true))
                        @can('employees.update')
                            <x-ui.button tone="ghost" size="sm" :href="route('hr.onboarding.required-policies')" wire:navigate>{{ __('hr_journeys.onboarding.open_policies_admin') }}</x-ui.button>
                        @endcan
                    @elseif ($key === 'contract')
                        @if ($isDone && $step['data']['contract_id'] && $canViewContracts)
                            <x-ui.button tone="ghost" size="sm" icon="eye" :href="route('hr.contracts.show', $step['data']['contract_id'])" wire:navigate>{{ __('hr_journeys.onboarding.open_contract') }}</x-ui.button>
                        @elseif ($step['state'] === 'todo' && $canCreateContracts)
                            <x-ui.button size="sm" icon="plus" :href="route('hr.contracts.create', ['employee' => $employeeId])" wire:navigate>{{ __('hr_journeys.onboarding.create_contract') }}</x-ui.button>
                        @endif
                    @elseif ($key === 'card' && $journey !== null)
                        @if ($isDone)
                            <x-ui.button tone="ghost" size="sm" wire:click="askConfirm('reopen-onboarding', 'card')">{{ __('hr_journeys.undo') }}</x-ui.button>
                        @elseif ($step['state'] === 'todo')
                            <x-ui.button size="sm" icon="check" wire:click="markCardHandedOver" data-test="card-handed-over">{{ __('hr_journeys.onboarding.card_action') }}</x-ui.button>
                        @endif
                    @elseif ($key === 'orientation' && $journey !== null)
                        @if ($isDone)
                            <x-ui.button tone="ghost" size="sm" wire:click="askConfirm('reopen-onboarding', 'orientation')">{{ __('hr_journeys.undo') }}</x-ui.button>
                        @else
                            <form wire:submit="scheduleOrientation" class="flex flex-wrap items-end gap-2">
                                <flux:input type="date" size="sm" wire:model="orientationDate" :label="__('hr_journeys.onboarding.orientation_date')" />
                                <x-ui.button type="submit" size="sm">{{ $step['data']['scheduled_for'] ? __('hr_journeys.onboarding.reschedule') : __('hr_journeys.onboarding.schedule') }}</x-ui.button>
                                @if ($step['data']['can_complete'])
                                    <x-ui.button type="button" tone="primary" size="sm" icon="check" wire:click="completeOrientation" data-test="orientation-done">{{ __('hr_journeys.onboarding.orientation_action') }}</x-ui.button>
                                @endif
                            </form>
                        @endif
                    @endif
                </div>
            </li>
        @endforeach
    </ol>

    @if ($mug !== null)
        {{-- The Mug is outside the journey: due once a contract that is not a trial starts. --}}
        <p class="border-t border-panel-border px-4 py-3 text-xs {{ $muted }}" data-onboarding-mug>
            <span class="font-semibold {{ $ink }}">{{ __('hr_journeys.onboarding.mug') }}:</span>
            {{ $mug['delivered'] ? __('hr_journeys.onboarding.mug_done') : ($mug['due'] ? __('hr_journeys.onboarding.mug_due') : __('hr_journeys.onboarding.mug_not_due')) }}
        </p>
    @endif
</x-ui.panel>
