{{-- An employee's status: icon + words + fill shape (Modules\Hr\Enums\EmployeeStatus). A draft or an
     awaiting record past its B5-D age gets a "Needs review" tag: it waits on the viewer. --}}
@php
    $employeeStatus = \Modules\Hr\Enums\EmployeeStatus::for($employee);
    $reviewAfter = match ($employeeStatus) {
        \Modules\Hr\Enums\EmployeeStatus::Incomplete => \Modules\Hr\Models\Employee::DRAFT_REVIEW_DAYS,
        \Modules\Hr\Enums\EmployeeStatus::AwaitingContract => \Modules\Hr\Models\Employee::AWAITING_REVIEW_DAYS,
        default => null,
    };
@endphp
<x-status :status="$employeeStatus" />
@if($reviewAfter !== null && ($employee->updated_at?->lt(now()->subDays($reviewAfter)) ?? false))
    <x-tag variant="act" class="ms-1" data-testid="needs-review">{{ __('employees.onboarding.needs-review') }}</x-tag>
@endif
