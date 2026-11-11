@props(['status'])

{{-- A contract's status (Modules\Hr\Enums\ContractStatus) as a tinted chip. --}}
@php
    $classes = match ($status) {
        \Modules\Hr\Enums\ContractStatus::Active => 'bg-status-good-soft text-status-good',
        \Modules\Hr\Enums\ContractStatus::Draft => 'bg-status-warn-soft text-status-warn',
        \Modules\Hr\Enums\ContractStatus::Inactive => 'bg-secondary-soft text-secondary-dark dark:bg-surface-2 dark:text-ink',
        default => 'bg-status-critical-soft text-status-critical',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold', $classes]) }} data-contract-status="{{ $status->value }}">{{ __('hr_contracts.status.'.$status->value) }}</span>
