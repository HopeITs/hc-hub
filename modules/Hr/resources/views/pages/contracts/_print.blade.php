{{--
    The print view of a contract (hr.contracts.print): the parties, the term,
    the schedule and the pay clause in English and Arabic side by side, for
    the browser's print-to-PDF. Replaces the old Browsershot PDF; the full
    multi-page legal text of the annual / trial / service templates is not
    reproduced here.
--}}
@use('Modules\Core\Models\Center')
@use('Modules\Core\Models\City')
@use('Modules\Core\Models\Program')
@use('Modules\Hr\Enums\ContractPlace')
@use('Modules\Hr\Enums\ContractType')
@use('Modules\Hr\Enums\Currency')
@use('Modules\Hr\Enums\PayMode')
@use('Modules\Hr\Enums\SalaryPer')
@use('Modules\Hr\Enums\ScheduleMode')
@use('Modules\Hr\Enums\SessionDuration')
@use('Modules\Hr\Support\Contracts\ContractOptions')
@use('Modules\Hr\Support\Employees\EnumLabel')

@php
    $employee = $contract->employee;
    $position = $contract->position;
    $both = function (string $key, array $replace = []): array {
        return [__($key, $replace, 'en'), __($key, $replace, 'ar')];
    };
    $date = fn (?\DateTimeInterface $value): string => $value?->format('Y-m-d') ?? '—';
    $time = fn (?string $value): string => $value === null || $value === '' ? '' : substr($value, 0, 5);
    $fixedSchedule = (int) $contract->schedule_mode !== ScheduleMode::Flexible->value;
    $enumBoth = function (string $enum, mixed $value): array {
        $locale = app()->getLocale();
        app()->setLocale('en');
        $en = EnumLabel::of($enum, $value);
        app()->setLocale('ar');
        $ar = EnumLabel::of($enum, $value);
        app()->setLocale($locale);

        return [$en, $ar];
    };
    $rows = [
        [$both('hr_contracts.print.employee'), [trim($employee?->first_name_en.' '.$employee?->father_name_en.' '.$employee?->last_name_en), trim($employee?->first_name_ar.' '.$employee?->father_name_ar.' '.$employee?->last_name_ar)]],
        [$both('hr_employees.fields.national_number'), [$employee?->national_number ?? '—', $employee?->national_number ?? '—']],
        [$both('hr_contracts.fields.position_id'), [$position?->title_en ?? '—', $position?->title_ar ?: ($position?->title_en ?? '—')]],
        [$both('hr_employees.fields.center'), [Center::nameOf($position?->center_id) ?: '—', Center::nameOf($position?->center_id) ?: '—']],
        [$both('common.city'), [City::nameOf($employee?->city_id) ?: '—', City::nameOf($employee?->city_id) ?: '—']],
        [$both('hr_contracts.fields.contract_type_id'), [ContractOptions::typeName($contract->contract_type_id), ContractOptions::typeName($contract->contract_type_id)]],
        [$both('hr_contracts.fields.type'), $enumBoth(ContractType::class, $contract->type)],
        [$both('hr_contracts.fields.place'), $enumBoth(ContractPlace::class, $contract->place)],
        [$both('hr_contracts.period'), [$date($contract->start_date).' → '.$date($contract->end_date), $date($contract->start_date).' ← '.$date($contract->end_date)]],
        [$both('hr_contracts.fields.weekly_hours'), [(string) $contract->weekly_hours, (string) $contract->weekly_hours]],
    ];

    if ($fixedSchedule) {
        $hours = $time($contract->work_hours_from).' – '.$time($contract->work_hours_to).($contract->work_hours_2_from ? ', '.$time($contract->work_hours_2_from).' – '.$time($contract->work_hours_2_to) : '');
        $rows[] = [$both('hr_contracts.schedule.hours'), [$hours, $hours]];

        if ($contract->work_day_from !== null) {
            $from = ContractOptions::WEEK_DAYS[$contract->work_day_from] ?? 'saturday';
            $to = ContractOptions::WEEK_DAYS[$contract->work_day_to] ?? 'saturday';
            $rows[] = [$both('hr_contracts.schedule.days'), [__('hr_contracts.days.'.$from, [], 'en').' – '.__('hr_contracts.days.'.$to, [], 'en'), __('hr_contracts.days.'.$from, [], 'ar').' – '.__('hr_contracts.days.'.$to, [], 'ar')]];
        }
    } else {
        $rows[] = [$both('hr_contracts.fields.schedule_mode'), $both('hr_contracts.schedule.flexible')];

        if ($contract->flex_custom_en || $contract->flex_custom_ar) {
            $rows[] = [$both('hr_contracts.schedule.custom'), [$contract->flex_custom_en ?? '', $contract->flex_custom_ar ?? '']];
        }
    }

    if ((int) $contract->pay_mode === PayMode::Flexible->value) {
        foreach ($contract->payTiers as $tier) {
            $amount = number_format((float) $tier->amount).' '.($enumBoth(Currency::class, $contract->currency)[0]);
            $rows[] = [[$tier->condition_en, $tier->condition_ar], [$amount, $amount]];
        }
    } elseif (filled($contract->salary)) {
        [$currencyEn, $currencyAr] = $enumBoth(Currency::class, $contract->currency);
        [$perEn, $perAr] = $enumBoth(SalaryPer::class, $contract->salary_per);
        $figure = number_format((float) $contract->salary);
        $rows[] = [$both('hr_contracts.fields.salary'), [$figure.' '.$currencyEn.' / '.$perEn, $figure.' '.$currencyAr.' / '.$perAr]];

        if ((int) $contract->salary_per === SalaryPer::Session->value) {
            $rows[] = [$both('hr_contracts.fields.session_duration'), $enumBoth(SessionDuration::class, $contract->session_duration)];
        }
    }
@endphp

<x-layouts::print :title="__('hr_contracts.print.title', [], 'en').' – '.trim($employee?->first_name_en.' '.$employee?->last_name_en)">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4 border-b border-secondary-soft pb-4">
        <div class="grid min-w-0 flex-1 grid-cols-2 gap-6">
            <h1 class="text-2xl font-bold" dir="ltr" lang="en">{{ __('hr_contracts.print.title', [], 'en') }}</h1>
            <h1 class="text-end text-2xl font-bold" dir="rtl" lang="ar">{{ __('hr_contracts.print.title', [], 'ar') }}</h1>
            <p class="text-sm text-secondary-medium" dir="ltr">{{ Program::nameOf($position?->program_id) }} · #{{ $contract->id }}</p>
            <p class="text-end text-sm text-secondary-medium" dir="rtl">{{ Program::nameOf($position?->program_id) }} · #{{ $contract->id }}</p>
        </div>
        <button type="button" onclick="window.print()" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white print:hidden" data-test="print-contract-button">
            {{ __('hr_contracts.actions.print') }}
        </button>
    </div>

    <table class="w-full border-collapse text-sm">
        @foreach ($rows as [$label, $value])
            <tr class="border-b border-secondary-soft align-top">
                <th class="w-1/5 py-2 pe-3 text-start font-semibold" dir="ltr" lang="en">{{ $label[0] }}</th>
                <td class="w-3/10 py-2 pe-3" dir="ltr" lang="en">{{ $value[0] }}</td>
                <td class="w-3/10 py-2 ps-3 text-end" dir="rtl" lang="ar">{{ $value[1] }}</td>
                <th class="w-1/5 py-2 ps-3 text-end font-semibold" dir="rtl" lang="ar">{{ $label[1] }}</th>
            </tr>
        @endforeach
    </table>

    <div class="mt-16 grid grid-cols-2 gap-10 text-sm">
        <div class="border-t border-secondary-dark pt-2">
            <p dir="ltr">{{ __('hr_contracts.print.employee_signature', [], 'en') }}</p>
            <p dir="rtl" class="text-end">{{ __('hr_contracts.print.employee_signature', [], 'ar') }}</p>
        </div>
        <div class="border-t border-secondary-dark pt-2">
            <p dir="ltr">{{ __('hr_contracts.print.employer_signature', [], 'en') }}</p>
            <p dir="rtl" class="text-end">{{ __('hr_contracts.print.employer_signature', [], 'ar') }}</p>
        </div>
    </div>
</x-layouts::print>
