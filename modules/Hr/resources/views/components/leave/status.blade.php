@props(['status' => null])

{{-- A leave request's status as a word in a tinted chip (pending / accepted / rejected). --}}
@php
    $case = \Modules\Hr\Enums\LeaveRequestStatus::of($status);
    $tone = match ($case) {
        \Modules\Hr\Enums\LeaveRequestStatus::Accepted => \App\Enums\StatusTone::Good,
        \Modules\Hr\Enums\LeaveRequestStatus::Rejected => \App\Enums\StatusTone::Critical,
        default => \App\Enums\StatusTone::Warn,
    };
@endphp

<x-ui.enum-badge :case="$case" :label="$case->label()" :tone="$tone" {{ $attributes }} data-leave-status="{{ $case->value }}" />
