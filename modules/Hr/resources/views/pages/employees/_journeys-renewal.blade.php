{{--
    The renewal journey of one expiring contract: expiry warning, HR's
    decision, the new contract and its leave balances. No pay figure is read
    or shown. Included by ⚡journeys.blade.php with $row.
--}}
@php
    $journey = $row['journey'];
    $contract = $journey->contract;
    $renewalSteps = $row['steps'];
    $settled = collect($renewalSteps)->whereIn('state', ['done', 'skipped'])->count();
@endphp

<x-ui.panel
    :title="__('hr_journeys.renewal.title', ['position' => $contract->position?->title ?? __('hr_journeys.unknown'), 'date' => $date($contract->end_date)])"
    data-journey="renewal"
    wire:key="renewal-journey-{{ $journey->id }}"
>
    <x-slot:toolbar>
        <span class="text-xs {{ $muted }}">
            @if ($journey->completed_at)
                {{ __('hr_journeys.renewal.completed_on', ['date' => $date($journey->completed_at)]) }}
            @else
                {{ __('hr_journeys.renewal.progress', ['done' => $settled, 'total' => count($renewalSteps)]) }}
            @endif
        </span>
    </x-slot:toolbar>

    <ol class="divide-y divide-panel-border">
        @foreach ($renewalSteps as $index => $step)
            @php
                $key = $step['key'];
                $data = $step['data'];
            @endphp
            <li class="grid gap-2 px-4 py-3 sm:grid-cols-[minmax(0,1fr)_auto]" wire:key="renewal-{{ $journey->id }}-{{ $key }}" data-step="{{ $key }}">
                <div class="min-w-0">
                    @include('hr::pages.employees._journeys-step-head', [
                        'label' => __('hr_journeys.renewal.steps.'.$key),
                        'afterLabel' => $step['blocked_by'] ? __('hr_journeys.renewal.steps.'.$step['blocked_by']) : null,
                    ])

                    @if ($key === 'warning')
                        @if ($data['last_sent_on'])
                            <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.renewal.warning_sent', ['date' => $date($data['last_sent_on']), 'count' => $data['count']]) }}</p>
                        @elseif ($step['note'])
                            <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.renewal.notes.'.$step['note']) }}</p>
                        @endif
                    @elseif ($key === 'decision')
                        @if ($data['decision'])
                            <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.renewal.decided_by', ['decision' => __('hr_journeys.renewal.decisions.'.$data['decision']), 'date' => $date($data['decided_at']), 'name' => $by($data['decided_by'])]) }}</p>
                        @elseif ($step['note'])
                            <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.renewal.notes.'.$step['note']) }}</p>
                        @else
                            <p class="mt-1 text-xs {{ $muted }}">{{ $data['auto_renew'] ? __('hr_journeys.renewal.auto_renew_on') : __('hr_journeys.renewal.auto_renew_off') }}</p>
                        @endif
                    @elseif ($key === 'successor')
                        @if ($data['contract_id'])
                            <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.renewal.successor_from', ['date' => $date($data['start_date'])]) }}</p>
                        @elseif ($step['note'])
                            <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.renewal.notes.'.$step['note'], ['date' => $date($data['auto_renew_on'])]) }}</p>
                        @endif
                    @elseif ($key === 'leave' && $step['note'])
                        <p class="mt-1 text-xs {{ $muted }}">{{ __('hr_journeys.renewal.notes.'.$step['note']) }}</p>
                    @endif
                </div>

                <div class="flex flex-wrap items-start gap-2 sm:justify-end">
                    @if ($key === 'decision')
                        @if ($data['can_decide'])
                            <x-ui.button tone="primary" size="sm" icon="check" wire:click="decideRenew({{ $journey->id }})" data-test="renewal-renew">{{ __('hr_journeys.renewal.renew') }}</x-ui.button>
                            @if ($data['can_stop'])
                                <x-ui.button size="sm" wire:click="askConfirm('stop', '{{ $journey->id }}')" data-test="renewal-stop">{{ __('hr_journeys.renewal.stop') }}</x-ui.button>
                            @endif
                        @elseif ($data['can_undo'])
                            <x-ui.button tone="ghost" size="sm" wire:click="askConfirm('undo', '{{ $journey->id }}')" data-test="renewal-undo">{{ __('hr_journeys.undo') }}</x-ui.button>
                        @endif
                    @elseif ($key === 'successor')
                        @if ($data['contract_id'] && $canViewContracts)
                            <x-ui.button tone="ghost" size="sm" icon="eye" :href="route('hr.contracts.show', $data['contract_id'])" wire:navigate>{{ __('hr_journeys.renewal.open_contract') }}</x-ui.button>
                        @elseif ($data['can_renew_by_hand'] && $row['canRenew'])
                            <x-ui.button size="sm" :href="route('hr.contracts.renew', $data['source_id'])" wire:navigate data-test="renewal-renew-by-hand">{{ __('hr_journeys.renewal.renew_by_hand') }}</x-ui.button>
                        @endif
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</x-ui.panel>
