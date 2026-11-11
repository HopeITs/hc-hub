{{--
    The Contracts tab of an employee profile: every contract the employee has
    held, newest first. Pay shows only to HR. Included by ⚡show.blade.php.
--}}
@use('Modules\Core\Models\Program')
@use('Modules\Hr\Enums\ContractStatus')
@use('Modules\Hr\Enums\Currency')
@use('Modules\Hr\Enums\SalaryPer')
@use('Modules\Hr\Support\Employees\EnumLabel')

@php
    $date = fn (?\DateTimeInterface $value): string => $value?->translatedFormat(__('formats.date')) ?? '—';
    $canOpenContracts = auth()->user()->can('contracts.view');
@endphp

@if ($this->contracts->isEmpty())
    <x-ui.empty-state icon="document-text" :heading="__('hr_employees.contracts.none')" :body="$canEdit ? __('hr_employees.contracts.none_body') : null" />
@else
    <x-ui.data-table :caption="__('hr_employees.tabs.contracts')">
        <x-slot:head>
            <th>{{ __('hr_employees.fields.position') }}</th>
            <th>{{ __('hr_employees.contracts.type') }}</th>
            <th>{{ __('hr_employees.contracts.period') }}</th>
            @if ($canViewFinancial)
                <th>{{ __('hr_employees.contracts.pay') }}</th>
            @endif
            <th class="w-36!">{{ __('common.status') }}</th>
            <th class="w-16!"><span class="sr-only">{{ __('common.actions') }}</span></th>
        </x-slot:head>

        @foreach ($this->contracts as $contract)
            <tr wire:key="profile-contract-{{ $contract->id }}">
                <td>
                    <span class="block font-semibold" dir="auto">{{ $contract->position?->title ?? '—' }}</span>
                    <span class="block text-xs {{ $muted }}">{{ $contract->position ? Program::nameOf($contract->position->program_id) : '' }}</span>
                </td>
                <td>{{ $contract->contractType?->name ?? '—' }}</td>
                <td>
                    {{ $date($contract->start_date) }} – {{ $date($contract->end_date) }}
                    @if ($contract->terminated_at !== null)
                        <span class="block text-xs text-status-critical">{{ __('hr_employees.contracts.terminated_on', ['date' => $date($contract->terminated_at)]) }}</span>
                    @endif
                </td>
                @if ($canViewFinancial)
                    <td class="tabular-nums">
                        @if (filled($contract->salary))
                            <span dir="ltr">{{ number_format((float) $contract->salary) }} {{ EnumLabel::of(Currency::class, $contract->currency, '') }}</span>
                            <span class="block text-xs {{ $muted }}">{{ __('hr_employees.contracts.per', ['unit' => EnumLabel::of(SalaryPer::class, $contract->salary_per, '')]) }}</span>
                        @else
                            —
                        @endif
                    </td>
                @endif
                <td><x-hr::contracts.status-badge :status="ContractStatus::for($contract)" /></td>
                <td>
                    @if ($canOpenContracts)
                        <x-ui.button tone="ghost" size="sm" icon="eye" :href="route('hr.contracts.show', $contract->id)" wire:navigate :aria-label="__('common.view_name', ['name' => $contract->position?->title ?? ''])" />
                    @endif
                </td>
            </tr>
        @endforeach
    </x-ui.data-table>
@endif
