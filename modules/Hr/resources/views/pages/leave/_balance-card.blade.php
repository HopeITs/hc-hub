@php
    /** @var \Modules\Hr\Models\LeaveBalance $balance */
    $format = fn (float $hours): string => \Modules\Hr\Support\Leave\LeaveFormat::rounded($hours, $perDay);
@endphp
{{-- One balance on "My leaves", in whole and half hours (LeaveFormat::rounded): the employee's own view. --}}
<div class="rounded-xl border border-secondary-soft bg-white p-4 dark:border-line dark:bg-surface-1" wire:key="balance-{{ $balance->id }}" data-balance="{{ $balance->id }}">
    <p class="type-label {{ $muted }}">{{ $balance->leaveType?->name }}</p>
    <p class="mt-1 text-lg font-semibold">{{ $format($eligibility->remainingHours($balance)) }}</p>
    <p class="mt-0.5 text-xs {{ $muted }}">{{ __('hr_leave.ui.used_of', ['used' => $format((float) $balance->used), 'total' => $format((float) $balance->deserved)]) }}</p>
</div>
