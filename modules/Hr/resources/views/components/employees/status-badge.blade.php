@props(['status'])

{{-- An employee's record status (Modules\Hr\Enums\EmployeeStatus) as a tinted chip. --}}
@php
    $classes = match ($status) {
        \Modules\Hr\Enums\EmployeeStatus::Active => 'bg-status-good-soft text-status-good',
        \Modules\Hr\Enums\EmployeeStatus::Incomplete => 'bg-status-warn-soft text-status-warn',
        \Modules\Hr\Enums\EmployeeStatus::AwaitingContract => 'bg-secondary-soft text-secondary-dark dark:bg-surface-2 dark:text-ink',
        default => 'bg-status-critical-soft text-status-critical',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold', $classes]) }} data-employee-status="{{ $status->value }}">{{ __('hr_employees.status.'.str_replace('-', '_', $status->value)) }}</span>
