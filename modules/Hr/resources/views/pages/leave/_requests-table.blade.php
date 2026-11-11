{{--
    A page of leave requests (relations from LeaveContext::load). Included by
    ⚡index ($mine: edit / withdraw while pending) and ⚡team ($showEmployee).
--}}
@php
    $pricing = app(\Modules\Hr\Support\Leave\LeaveRequestPricing::class);
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $pending = \Modules\Hr\Enums\LeaveRequestStatus::Pending->value;
@endphp

<x-ui.data-table :caption="__('hr_leave.titles.requests')">
    <x-slot:head>
        @if ($showEmployee)
            <th>{{ __('hr_leave.fields.employee') }}</th>
        @endif
        <th>{{ __('hr_leave.fields.leave_balance_id') }}</th>
        <th>{{ __('hr_leave.ui.period') }}</th>
        <th class="w-40!">{{ __('hr_leave.ui.duration') }}</th>
        <th class="w-32!">{{ __('hr_leave.ui.status') }}</th>
        <th class="w-28!"><span class="sr-only">{{ __('common.actions') }}</span></th>
    </x-slot:head>

    @foreach ($requests as $leaveRequest)
        @php
            $contract = $leaveRequest->leaveBalance?->contract;
            $perDay = $contract === null ? null : $pricing->hoursPerDayFor($contract);
            $employeeName = (string) $contract?->employee?->name;
        @endphp
        <tr wire:key="leave-request-{{ $leaveRequest->id }}" data-leave-request="{{ $leaveRequest->id }}">
            @if ($showEmployee)
                <x-ui.data-table.cell :label="__('hr_leave.fields.employee')">
                    <span class="font-semibold" dir="auto">{{ $employeeName }}</span>
                    @if ($contract?->employee && ! $contract->employee->is_active)
                        <span class="text-xs {{ $muted }}">{{ __('hr_leave.ui.former_staff') }}</span>
                    @endif
                </x-ui.data-table.cell>
            @endif
            <x-ui.data-table.cell :label="__('hr_leave.fields.leave_balance_id')">{{ $leaveRequest->leaveBalance?->leaveType?->name }}</x-ui.data-table.cell>
            <x-ui.data-table.cell :label="__('hr_leave.ui.period')"><span class="tabular-nums">{{ \Modules\Hr\Support\Leave\LeaveFormat::period($leaveRequest) }}</span></x-ui.data-table.cell>
            <x-ui.data-table.cell :label="__('hr_leave.ui.duration')">{{ \Modules\Hr\Support\Leave\LeaveFormat::duration($leaveRequest, $perDay) }}</x-ui.data-table.cell>
            <x-ui.data-table.cell :label="__('hr_leave.ui.status')">
                <x-hr::leave.status :status="$leaveRequest->status" />
                @if ($leaveRequest->status !== $pending && $leaveRequest->hr_approved)
                    <span class="block text-xs {{ $muted }}">{{ __('hr_leave.ui.hr_seen') }}</span>
                @endif
            </x-ui.data-table.cell>
            <td class="px-3.5 py-2.5">
                <span class="flex items-center justify-end gap-1">
                    <x-ui.button tone="ghost" size="sm" icon="eye" class="size-8!" :href="route('hr.leave.show', $leaveRequest->id)" wire:navigate :aria-label="__('hr_leave.ui.open_request')" />
                    @if ($mine && $leaveRequest->status === $pending)
                        <x-ui.button tone="ghost" size="sm" icon="pencil-square" class="size-8!" wire:click="openEditForm({{ $leaveRequest->id }})" :aria-label="__('hr_leave.ui.edit_request')" />
                        <x-ui.button tone="ghost" size="sm" icon="trash" class="size-8! text-status-critical!" wire:click="confirmWithdraw({{ $leaveRequest->id }})" :aria-label="__('hr_leave.ui.withdraw')" />
                    @endif
                </span>
            </td>
        </tr>
    @endforeach
</x-ui.data-table>
