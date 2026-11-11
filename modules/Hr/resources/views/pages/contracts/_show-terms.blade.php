{{--
    A contract's schedule and pay. Reads $contract and $canViewPay (pay
    columns are only selected when it is true). Included by ⚡show.blade.php.
--}}
@use('Modules\Hr\Enums\Currency')
@use('Modules\Hr\Enums\PayMode')
@use('Modules\Hr\Enums\SalaryPer')
@use('Modules\Hr\Enums\ScheduleMode')
@use('Modules\Hr\Enums\SessionDuration')
@use('Modules\Hr\Support\Contracts\ContractOptions')
@use('Modules\Hr\Support\Employees\EnumLabel')

@php
    $time = fn (?string $value): string => $value === null || $value === '' ? '' : substr($value, 0, 5);
    $day = fn (?int $value): string => $value === null ? '' : __('hr_contracts.days.'.(ContractOptions::WEEK_DAYS[$value] ?? 'saturday'));
    $fixedSchedule = (int) $contract->schedule_mode !== ScheduleMode::Flexible->value;
@endphp

<x-ui.panel :title="__('hr_contracts.form.schedule')">
    <dl class="grid gap-4 p-4 @2xl/record:grid-cols-2">
        <x-hr::employees.fact :label="__('hr_contracts.fields.schedule_mode')" :value="$fixedSchedule ? __('hr_contracts.schedule.fixed') : __('hr_contracts.schedule.flexible')" />
        <x-hr::employees.fact :label="__('hr_contracts.fields.weekly_hours')" :value="$contract->weekly_hours" />
        @if ($fixedSchedule)
            <x-hr::employees.fact :label="__('hr_contracts.schedule.days')" :value="$contract->work_day_from === null ? null : $day($contract->work_day_from).' – '.$day($contract->work_day_to)" />
            <x-hr::employees.fact :label="__('hr_contracts.schedule.hours')" ltr>
                {{ $time($contract->work_hours_from) }} – {{ $time($contract->work_hours_to) }}
                @if ($contract->work_hours_2_from)
                    , {{ $time($contract->work_hours_2_from) }} – {{ $time($contract->work_hours_2_to) }}
                @endif
            </x-hr::employees.fact>
        @else
            <x-hr::employees.fact :label="__('hr_contracts.fields.flex_days_per_week')" :value="$contract->flex_days_per_week" />
            <x-hr::employees.fact :label="__('hr_contracts.fields.flex_hours_per_month')" :value="$contract->flex_hours_per_month" />
            @if ($contract->flex_varies_by_requirements)
                <x-hr::employees.fact :label="__('hr_contracts.fields.flex_varies_by_requirements')" :value="__('hr_contracts.yes')" />
            @endif
            @if ($contract->flex_coordinated_with_manager)
                <x-hr::employees.fact :label="__('hr_contracts.fields.flex_coordinated_with_manager')" :value="__('hr_contracts.yes')" />
            @endif
            <x-hr::employees.fact class="@2xl/record:col-span-2" :label="__('hr_contracts.schedule.custom')" :value="app()->getLocale() === 'ar' ? $contract->flex_custom_ar : $contract->flex_custom_en" />
        @endif
    </dl>

    @if ($canViewPay)
        <div class="border-t border-panel-border">
            <p class="px-4 pt-4 text-base font-bold">{{ __('hr_contracts.form.pay') }}</p>
            <dl class="grid gap-4 p-4 @2xl/record:grid-cols-2" data-contract-pay>
                <x-hr::employees.fact :label="__('hr_contracts.fields.pay_mode')" :value="EnumLabel::of(PayMode::class, $contract->pay_mode)" />
                <x-hr::employees.fact :label="__('hr_contracts.fields.currency')" :value="EnumLabel::of(Currency::class, $contract->currency)" />
                @if ((int) $contract->pay_mode === PayMode::Flexible->value)
                    <div class="@2xl/record:col-span-2">
                        <dt class="type-label text-secondary-medium dark:text-ink-muted">{{ __('hr_contracts.fields.pay_tiers') }}</dt>
                        <dd>
                            <ul class="mt-1 flex flex-col gap-1 text-sm">
                                @forelse ($contract->payTiers as $tier)
                                    <li><span dir="auto">{{ app()->getLocale() === 'ar' ? $tier->condition_ar : $tier->condition_en }}</span>: <b class="tabular-nums" dir="ltr">{{ number_format((float) $tier->amount) }}</b></li>
                                @empty
                                    <li>—</li>
                                @endforelse
                            </ul>
                        </dd>
                    </div>
                @else
                    <x-hr::employees.fact :label="__('hr_contracts.fields.salary')" :value="filled($contract->salary) ? number_format((float) $contract->salary) : null" ltr />
                    <x-hr::employees.fact :label="__('hr_contracts.fields.salary_per')" :value="EnumLabel::of(SalaryPer::class, $contract->salary_per)" />
                    @if ((int) $contract->salary_per === SalaryPer::Session->value)
                        <x-hr::employees.fact :label="__('hr_contracts.fields.session_duration')" :value="EnumLabel::of(SessionDuration::class, $contract->session_duration)" />
                    @endif
                @endif
            </dl>
        </div>
    @endif
</x-ui.panel>
