@props(['contract'])

{{--
    The working-hours clause of the printed contract and the amendment sheet,
    in English and Arabic side by side, worded from the same schedule_mode,
    flex_* and show_* columns the contract form writes. Ported from HR's
    <x-contract.schedule-clause>. The bilingual wording is the legal text of
    the contract itself, printed in both languages at once, so it lives here
    and not in a lang file. Days are stored Sunday = 0 … Saturday = 6.
--}}
@php
    $boldTime = fn (string $v): string => '<strong>'.$v.'</strong>';

    $dayNamesEn = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $dayNamesAr = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

    $hoursBetween = function (?string $from, ?string $to): float {
        if (! $from || ! $to) {
            return 0.0;
        }

        $minutes = \Illuminate\Support\Carbon::parse($from)->diffInMinutes(\Illuminate\Support\Carbon::parse($to), false);

        return $minutes > 0 ? round($minutes / 60, 2) : 0.0;
    };

    $to12Hour = fn (?string $value): string => $value ? \Illuminate\Support\Carbon::parse($value)->format('g:i A') : '';
    $to12HourAr = fn (?string $value): string => $value
        ? \Illuminate\Support\Carbon::parse($value)->format('g:i').' '.(\Illuminate\Support\Carbon::parse($value)->hour < 12 ? 'ص' : 'م')
        : '';

    $rows = [];

    if ((int) $contract->schedule_mode === \Modules\Hr\Enums\ScheduleMode::Flexible->value) {
        $fragmentsEn = [];
        $fragmentsAr = [];

        if ($contract->flex_hours_per_week) {
            $fragmentsEn[] = ((string) $contract->flex_hours_per_week).' hours per week';
            $fragmentsAr[] = $contract->flex_hours_per_week.' ساعة أسبوعيًا';
        }

        if ($contract->flex_days_per_week) {
            $fragmentsEn[] = ((string) $contract->flex_days_per_week).' days per week';
            $fragmentsAr[] = $contract->flex_days_per_week.' '.((int) $contract->flex_days_per_week >= 3 ? 'أيام' : 'يوم').' أسبوعيًا';
        }

        if ($contract->flex_hours_per_month) {
            $fragmentsEn[] = ((string) $contract->flex_hours_per_month).' hours per month';
            $fragmentsAr[] = $contract->flex_hours_per_month.' ساعة شهريًا';
        }

        if ($contract->flex_varies_by_requirements) {
            $fragmentsEn[] = 'hours vary according to the requirements of the job';
            $fragmentsAr[] = 'الساعات تتغير بحسب متطلبات العمل';
        }

        if ($contract->flex_coordinated_with_manager) {
            $fragmentsEn[] = 'working days and hours are coordinated with the direct manager';
            $fragmentsAr[] = 'تُحدَّد أيام وساعات العمل بالتنسيق مع المدير المباشر';
        }

        if (filled($contract->flex_custom_en)) {
            $fragmentsEn[] = e(rtrim($contract->flex_custom_en, '.'));
        }

        if (filled($contract->flex_custom_ar)) {
            $fragmentsAr[] = e(rtrim($contract->flex_custom_ar, '.'));
        }

        if ($fragmentsEn || $fragmentsAr) {
            $rows[] = [
                'en' => $fragmentsEn ? ucfirst(implode(', ', $fragmentsEn)).'.' : '—',
                'ar' => $fragmentsAr ? implode('، ', $fragmentsAr).'.' : '—',
            ];
        }
    } else {
        $clausesEn = [];
        $clausesAr = [];

        if ($contract->show_hours_per_week) {
            $clausesEn[] = '<strong>'.((int) $contract->weekly_hours).' working hours</strong> per week';
            $clausesAr[] = '<strong>'.((int) $contract->weekly_hours).' ساعة</strong> عمل أسبوعيًا';
        }

        if ($contract->show_hours_per_day) {
            $dailyHours = $hoursBetween($contract->work_hours_from, $contract->work_hours_to)
                + $hoursBetween($contract->work_hours_2_from, $contract->work_hours_2_to);

            $clausesEn[] = '<strong>'.$dailyHours.' hours</strong> per day';
            $clausesAr[] = '<strong>'.$dailyHours.' ساعات</strong> يوميًا';
        }

        if ($contract->show_days_per_week && $contract->work_day_from !== null && $contract->work_day_to !== null) {
            $daysCount = (int) $contract->work_day_to - (int) $contract->work_day_from + 1;

            $clausesEn[] = '<strong>'.$daysCount.' days</strong> per week';
            $clausesAr[] = '<strong>'.$daysCount.' أيام</strong> أسبوعيًا';
        }

        if ($clausesEn || $clausesAr) {
            $rows[] = [
                'en' => $clausesEn ? implode(', ', $clausesEn).'.' : '—',
                'ar' => $clausesAr ? implode('، ', $clausesAr).'.' : '—',
            ];
        }

        $hasRange = $contract->show_day_range && $contract->work_day_from !== null && $contract->work_day_to !== null;
        $rangeEn = '';
        $rangeAr = '';

        if ($hasRange) {
            $fromEn = $dayNamesEn[(int) $contract->work_day_from] ?? '';
            $toEn = $dayNamesEn[(int) $contract->work_day_to] ?? '';
            $fromAr = $dayNamesAr[(int) $contract->work_day_from] ?? '';
            $toAr = $dayNamesAr[(int) $contract->work_day_to] ?? '';

            $rangeEn = $fromEn === $toEn ? $fromEn : "{$fromEn} to {$toEn}";
            $rangeAr = $fromAr === $toAr ? $fromAr : "من {$fromAr} إلى {$toAr}";
        }

        $fromTime = $boldTime($to12Hour($contract->work_hours_from));
        $toTime = $boldTime($to12Hour($contract->work_hours_to));
        $fromTimeAr = $boldTime($to12HourAr($contract->work_hours_from));
        $toTimeAr = $boldTime($to12HourAr($contract->work_hours_to));

        $officeHoursEn = 'Standard office hours are'.($hasRange ? " {$rangeEn} and" : '')." from {$fromTime} to {$toTime}";
        $officeHoursAr = $hasRange
            ? "ساعات الدوام الرسمية هي {$rangeAr} ومن الساعة {$fromTimeAr} حتى الساعة {$toTimeAr}"
            : "ساعات الدوام الرسمية هي من الساعة {$fromTimeAr} حتى الساعة {$toTimeAr}";

        if (filled($contract->work_hours_2_from) && filled($contract->work_hours_2_to)) {
            $officeHoursEn .= ' and from '.$boldTime($to12Hour($contract->work_hours_2_from)).' to '.$boldTime($to12Hour($contract->work_hours_2_to));
            $officeHoursAr .= ' ومن الساعة '.$boldTime($to12HourAr($contract->work_hours_2_from)).' حتى الساعة '.$boldTime($to12HourAr($contract->work_hours_2_to));
        }

        $rows[] = ['en' => $officeHoursEn.'.', 'ar' => $officeHoursAr.'.'];
    }
@endphp

@foreach ($rows as $row)
    <x-hr::contract-legal.dual class="mt-1" :en="$row['en']" :ar="$row['ar']" />
@endforeach
