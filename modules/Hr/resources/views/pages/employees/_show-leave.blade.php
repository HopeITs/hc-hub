{{--
    The Leave tab of an employee profile: the balances of the running
    contract, read only. Balances are kept in hours; days are the hours over
    the contract's daily hours. Included by ⚡show.blade.php.
--}}
@php
    $dailyHours = max(1, (int) floor(((int) ($currentContract?->weekly_hours ?? 40)) / 5));
    $days = fn (float|int $hours): string => rtrim(rtrim(number_format($hours / $dailyHours, 1), '0'), '.');
@endphp

@if ($this->leaveBalances->isEmpty())
    <x-ui.empty-state icon="calendar-days" :heading="__('hr_employees.leave.none')" :body="__('hr_employees.leave.none_body')" />
@else
    <x-ui.data-table :caption="__('hr_employees.tabs.leave')">
        <x-slot:head>
            <th>{{ __('hr_employees.leave.type') }}</th>
            <th class="w-36!">{{ __('hr_employees.leave.deserved') }}</th>
            <th class="w-36!">{{ __('hr_employees.leave.used') }}</th>
            <th class="w-36!">{{ __('hr_employees.leave.remaining') }}</th>
        </x-slot:head>

        @foreach ($this->leaveBalances as $balance)
            @php
                $remaining = (float) $balance->deserved - (float) $balance->used;
            @endphp
            <tr wire:key="leave-balance-{{ $balance->id }}">
                <td class="font-semibold" dir="auto">{{ $balance->leaveType?->name ?? '—' }}</td>
                <td class="tabular-nums">{{ trans_choice('hr_employees.leave.count_days', (float) $days($balance->deserved), ['count' => $days($balance->deserved)]) }}</td>
                <td class="tabular-nums">{{ trans_choice('hr_employees.leave.count_days', (float) $days($balance->used), ['count' => $days($balance->used)]) }}</td>
                <td @class(['tabular-nums font-semibold', 'text-status-critical' => $remaining < 0])>{{ trans_choice('hr_employees.leave.count_days', (float) $days($remaining), ['count' => $days($remaining)]) }}</td>
            </tr>
        @endforeach
    </x-ui.data-table>
@endif
