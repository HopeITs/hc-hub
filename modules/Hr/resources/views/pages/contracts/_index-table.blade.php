{{--
    The contracts register's table. Included by ⚡index.blade.php; reads
    $contracts (the paginator) and $hasFilters. No pay here.
--}}
@use('Modules\Core\Models\City')
@use('Modules\Core\Models\Program')
@use('Modules\Hr\Enums\ContractStatus')
@use('Modules\Hr\Support\Contracts\ContractOptions')

@php
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $date = fn (?\DateTimeInterface $value): string => $value?->translatedFormat(__('formats.date')) ?? '—';
    $canUpdate = auth()->user()->can('contracts.update');
@endphp

@if ($contracts->isEmpty())
    <x-ui.empty-state icon="document-text" :heading="__('hr_contracts.empty.heading')" :body="$hasFilters ? __('common.change_the_search_or_filters_and_try_again') : __('hr_contracts.empty.body')">
        @if ($hasFilters)
            <x-slot:action>
                <x-ui.button tone="primary" icon="arrow-path" wire:click="clearFilters">{{ __('common.reset_filters') }}</x-ui.button>
            </x-slot:action>
        @endif
    </x-ui.empty-state>
@else
    <x-ui.data-table :caption="__('hr_contracts.titles.index')">
        <x-slot:head>
            <th>{{ __('hr_contracts.fields.employee_id') }}</th>
            <th>{{ __('hr_contracts.fields.position_id') }}</th>
            <th class="w-36!">{{ __('hr_contracts.fields.contract_type_id') }}</th>
            <th class="w-56!">{{ __('hr_contracts.period') }}</th>
            <th class="w-32!">{{ __('common.status') }}</th>
            <th class="w-28!"><span class="sr-only">{{ __('common.actions') }}</span></th>
        </x-slot:head>

        @foreach ($contracts as $contract)
            <tr wire:key="contract-{{ $contract->id }}" data-contract="{{ $contract->id }}">
                <td>
                    <span class="block truncate font-semibold" dir="auto">{{ $contract->employee?->name ?? '—' }}</span>
                    <span class="block truncate text-xs {{ $muted }}">{{ City::nameOf($contract->employee?->city_id) }}</span>
                </td>
                <td>
                    <span class="block" dir="auto">{{ $contract->position?->title ?? '—' }}</span>
                    <span class="block text-xs {{ $muted }}">{{ $contract->position ? Program::nameOf($contract->position->program_id) : '' }}</span>
                </td>
                <td>{{ ContractOptions::typeName($contract->contract_type_id) }}</td>
                <td class="tabular-nums">
                    {{ $date($contract->start_date) }} – {{ $date($contract->end_date) }}
                    @if ($contract->auto_renew && ! $contract->isTerminated())
                        <span class="block text-xs {{ $muted }}">{{ __('hr_contracts.auto_renews') }}</span>
                    @endif
                </td>
                <td><x-hr::contracts.status-badge :status="ContractStatus::for($contract)" /></td>
                <td>
                    <span class="flex items-center justify-end gap-1">
                        <x-ui.button tone="ghost" size="sm" icon="eye" :href="route('hr.contracts.show', $contract->id)" wire:navigate :aria-label="__('common.view_name', ['name' => $contract->employee?->name ?? ''])" />
                        @if ($canUpdate)
                            <x-ui.button tone="ghost" size="sm" icon="pencil-square" :href="route('hr.contracts.edit', $contract->id)" wire:navigate :aria-label="__('common.edit_name', ['name' => $contract->employee?->name ?? ''])" />
                        @endif
                    </span>
                </td>
            </tr>
        @endforeach
    </x-ui.data-table>

    <div class="mt-3">
        <flux:pagination :paginator="$contracts" scroll-to="#hr-contracts-heading" />
    </div>
@endif
