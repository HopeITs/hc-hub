@php
   use App\Support\ArabicText as AR;
    use Carbon\Carbon;

    $forPdf = $forPdf ?? false;
    $Glyphs = fn($s) => $forPdf ? AR::glyphs((string) $s) : (string) $s;
    $Digits = fn($s) => $forPdf ? ar_digits((string) $s) : (string) $s;

    $pos = $contract->position;
    $req = $pos?->requiredPosition;
    $positionTitleEn = $req?->title_en ?? ($pos?->title_en ?? '—');
    $positionTitleAr = $req?->title_ar ?? ($pos?->title_ar ?? '—');

    $startStr = $contract->start_date ? Carbon::parse($contract->start_date)->format('d/m/Y') : '—';
    $endStr = $contract->end_date ? Carbon::parse($contract->end_date)->format('d/m/Y') : '—';
    $startStrAr = $Digits($startStr);
    $endStrAr   = $Digits($endStr);
    $salary = (float) ($contract->salary ?? 0);
    $salaryFormattedEn = $salary ? number_format($salary, 0, '.', ',') : '—';
    $salaryFormattedAr = $salary ? $Digits(number_format($salary, 0, '.', ',')) : '—';
    $annualLeaveDays = $contract->leaveBalances->first()?->int_total ?? '—';
    $weeklyHours = (int) ($contract->weekly_hours ?? 40);
    $dailyHours = (int) ($contract->daily_hours ?? 8);
    $workDaysPerWeek = (int) ($contract->work_days_per_week ?? 5);

    $workDaysEn = $contract->work_days_en ?: 'Monday to Friday';
    $workDaysAr = $contract->work_days_ar ?: 'الإثنين إلى الجمعة';
    $workDaysEnForClause = trim(preg_replace('/^\s*from\s+/i', '', $workDaysEn));
    $workDaysArForClause = trim(preg_replace('/^\s*من\s+/u', '', $workDaysAr));

    $dailyStart = $contract->work_hours_from ? Carbon::parse($contract->work_hours_from)->format('g:i A') : '9:00 AM';
    $dailyEnd   = $contract->work_hours_to ? Carbon::parse($contract->work_hours_to)->format('g:i A') : '5:00 PM';
    $secondShiftStart = $contract->second_shift_work_hours_from ? Carbon::parse($contract->second_shift_work_hours_from)->format('g:i A') : '3:00 PM';
    $secondShiftEnd = $contract->second_shift_work_hours_to ? Carbon::parse($contract->second_shift_work_hours_to)->format('g:i A') : '7:00 PM';

    $esc = fn($v) => e((string) ($v ?: '—'));
    $escAr = fn($v) => e((string) ($v ? $Glyphs($v) : '—'));
    $LB = fn($t) => '<span class="h-label-bold">' . $t . '</span>';
    $wrapLtr = fn ($v) => '<span dir="ltr">'.$v.'</span>';
    $programId = $contract->position?->program_id;

    $project_type = contract_project_type($contract);
    $isSplitShift = (bool) $contract->is_split_shift || $contract->shift_type === 'split';
    $weekendDay = contract_primary_weekend_day($pos?->center?->weekends);
    $weekendDaysEn = [
        0 => 'Sundays', 1 => 'Mondays', 2 => 'Tuesdays', 3 => 'Wednesdays',
        4 => 'Thursdays', 5 => 'Fridays', 6 => 'Saturdays',
    ];
    $weekendDaysAr = [
        0 => 'الاحد', 1 => 'الإثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء',
        4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت',
    ];
    $weekendClauseEn = $weekendDay === null ? '' : $weekendDaysEn[$weekendDay].' are considered working days when necessary and not treated as extra hours.';
    $weekendClauseAr = $weekendDay === null ? '' : $Glyphs('يعتبر يوم '.$weekendDaysAr[$weekendDay].' يوم عمل في حال رأت الإدارة الحاجة لذلك، ولا يعتبر دوام إضافي.');
    $dayOffDay = $weekendDay ?? 0;
    $dayOffDaysEn = [
        0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
        4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday',
    ];
    $dayOffDaysAr = [
        0 => 'الأحد', 1 => 'الإثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء',
        4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت',
    ];
    $dayOffClauseEn = $dayOffDaysEn[$dayOffDay].' is considered a day off, unless management deems it necessary, and is not considered overtime.';
    $dayOffClauseAr = $Glyphs('يعتبر يوم '.$dayOffDaysAr[$dayOffDay].' يوم عطلة، إلا في حال رأت الإدارة الحاجة لذلك، ولا يعتبر دوام إضافي.');

    $isStudyZone  = ($programId == 8); 
    $isGeneralProject = !$isStudyZone;
  
@endphp

<div class="avoid-break mt-4">
    <x-contract.dual 
        :en="$LB('4. Job description:')" 
        :ar="$LB($Glyphs('4. وصف العمل:'))" />

    <x-contract.dual class="mt-1"
        :en="'<strong>' . $esc($positionTitleEn) . '</strong>'" 
        :ar="'<strong>' . $escAr($positionTitleAr) . '</strong>'" />
</div>
<div class="avoid-break mt-4">
    <x-contract.dual 
        :en="$LB('5. Duration of the contract:')" 
        :ar="$LB($Glyphs('5. مدة العقد (الفترة التجريبية):'))" />

    <x-contract.dual class="mt-1"
        :en="'Start date: <strong>' . $esc($startStr) . '</strong>'" 
        :ar="$Glyphs('تبدأ من تاريخ:') . ' <strong>' . $wrapLtr($esc($startStrAr)) . '</strong>'" />

    <x-contract.dual 
        :en="'End date: <strong>' . $esc($endStr) . '</strong>'" 
        :ar="$Glyphs('وتنتهي إلى تاريخ:') . ' <strong>' . $wrapLtr($esc($endStrAr)) . '</strong>'" />

       <x-contract.dual class="mt-1"
        :en="'It can be extended by mutual agreement between the two parties, based on performance evaluation, provided that the trial period does not exceed six months. The trial period ends with either the confirmation of the second team with a voluntary contract or their dismissal, based on a clear performance evaluation.'"
        :ar="$Glyphs('وهي قابلة للتمديد بالاتفاق بين الفريقين ووفقاً لتقييم الأداء، على ألا تتجاوز مدة الفترة التجريبية ستة أشهر. تنتهي الفترة التجريبية بتثبيت الفريق الثاني بعقد تطوّع أو الاعتذار منه وذلك وفق تقييم أداء واضح.')" />

</div> 


<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('6. Volunteer Work Hours:')" :ar="$LB($Glyphs('6. ساعات العمل التطوعّي:'))" />

    @switch($project_type)
        @case('cu')
            <x-contract.dual class="mt-1"
                :en="'<strong>' . $weeklyHours . ' working hours</strong> per week, <strong>' . $dailyHours . ' hours</strong> per day.'"
                :ar="'<strong>' . $Digits($weeklyHours) . ' ' . $Glyphs('ساعة') . '</strong> ' . $Glyphs('عمل أسبوعياً،') . ' <strong>' . $Digits($dailyHours) . ' ' . $Glyphs('ساعات') . '</strong> ' . $Glyphs('يومياً.')" />
            <x-contract.dual class="mt-1"
                :en="'Standard office hours are ' . $workDaysEnForClause . ' from ' . $dailyStart . ' to ' . $dailyEnd . '.'"
                :ar="$Glyphs('من نهار ') . $Glyphs($workDaysArForClause) . ' ' . $Glyphs('من الساعة ') . $wrapLtr($dailyStart) . ' ' . $Glyphs('حتى الساعة ') . $wrapLtr($dailyEnd) . '.'" />
            @if($weekendClauseEn)
                <x-contract.dual class="mt-1" :en="$weekendClauseEn" :ar="$weekendClauseAr"/>
            @endif
            @break

        @case('disc_leap')
            <x-contract.dual class="mt-1"
                :en="'<strong>' . $weeklyHours . ' Working hours</strong> It changes according to the requirments of the job.'"
                :ar="'<strong dir=&quot;ltr&quot;>' . $Digits($weeklyHours) . '</strong> ' . $Glyphs('ساعة أسبوعياً تتغير بحسب متطلبات العمل يومياً.')" />
            @if($weekendClauseEn)
                <x-contract.dual class="mt-1" :en="$weekendClauseEn" :ar="$weekendClauseAr"/>
            @endif
            @break

        @case('studyzone')
            @if($isSplitShift)
                <x-contract.dual class="mt-1"
                    :en="'<strong>' . $dailyHours . ' hours</strong> per day, <strong>' . $workDaysPerWeek . ' days</strong> per week, Standard office hours are from ' . $dailyStart . ' to ' . $dailyEnd . ' and from ' . $secondShiftStart . ' to ' . $secondShiftEnd . '.'"
                    :ar="'<strong>' . $Digits($dailyHours) . ' ' . $Glyphs('ساعات') . '</strong> ' . $Glyphs('يومياً،') . ' <strong>' . $Digits($workDaysPerWeek) . ' ' . $Glyphs('أيام') . '</strong> ' . $Glyphs('أسبوعياً، من الساعة ') . $wrapLtr($dailyStart) . ' ' . $Glyphs('حتى الساعة ') . $wrapLtr($dailyEnd) . ' ' . $Glyphs('و') . ' ' . $wrapLtr($secondShiftStart) . ' ' . $Glyphs('حتى الساعة ') . $wrapLtr($secondShiftEnd) . '.'" />
            @else
                <x-contract.dual class="mt-1"
                    :en="'<strong>' . $dailyHours . ' hours</strong> per day, <strong>' . $workDaysPerWeek . ' days</strong> per week, Standard office hours are from ' . $dailyStart . ' to ' . $dailyEnd . '.'"
                    :ar="'<strong>' . $Digits($dailyHours) . ' ' . $Glyphs('ساعات') . '</strong> ' . $Glyphs('يومياً،') . ' <strong>' . $Digits($workDaysPerWeek) . ' ' . $Glyphs('أيام') . '</strong> ' . $Glyphs('أسبوعياً، من الساعة ') . $wrapLtr($dailyStart) . ' ' . $Glyphs('حتى الساعة ') . $wrapLtr($dailyEnd) . '.'" />
            @endif
            <x-contract.dual class="mt-1"
                :en="$dayOffClauseEn"
                :ar="$dayOffClauseAr" />
            <x-contract.dual class="mt-1"
                :en="'Volunteers are entitled to a second day off in the week, to be determined in coordination with management and based on work requirements.'"
                :ar="$Glyphs('يحق للمتطوع يوم عطلة ثانٍ ضمن الأسبوع، يتم تحديده بالتنسيق مع الإدارة وما يتطلبه العمل. يسمح بتعديل أوقات الدوام والعطل في الأشهر التي تتطلب فتح المركز 7 أيام، وذلك بالتنسيق مع المدير المباشر ومسؤول الموارد البشرية.')"/>
            @break

        @default
            <x-contract.dual class="mt-1"
                :en="'<strong>' . $weeklyHours . ' working hours</strong> per week, <strong>' . $dailyHours . ' hours</strong> per day.'"
                :ar="'<strong>' . $Digits($weeklyHours) . ' ' . $Glyphs('ساعة') . '</strong> ' . $Glyphs('عمل أسبوعياً،') . ' <strong>' . $Digits($dailyHours) . ' ' . $Glyphs('ساعات') . '</strong> ' . $Glyphs('يومياً.')" />
            <x-contract.dual class="mt-1"
                :en="'Standard office hours are ' . $workDaysEnForClause . ' from ' . $dailyStart . ' to ' . $dailyEnd . '.'"
                :ar="$Glyphs('من نهار ') . $Glyphs($workDaysArForClause) . ' ' . $Glyphs('من الساعة ') . $wrapLtr($dailyStart) . ' ' . $Glyphs('حتى الساعة ') . $wrapLtr($dailyEnd) . '.'" />
            @if($weekendClauseEn)
                <x-contract.dual class="mt-1" :en="$weekendClauseEn" :ar="$weekendClauseAr"/>
            @endif
    @endswitch


</div>

<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('7. Stipend and Benefits:')" :ar="$LB($Glyphs('7. المكافأة المالية والمزايا:'))" />
<x-contract.dual class="mt-1"
    :en="'Christian Hope Center is to give a monthly stipend of <strong>/ ' . $salaryFormattedEn . ' USD /</strong>'" 
    :ar="$Glyphs('خصص الفريق الأول للفريق الثاني شهرياً مكافأة وقدرها ') . '<strong>' . $Glyphs('/') . $salaryFormattedAr . ' ' . $Glyphs('دولار أمريكي /') . '</strong>'" />

    <x-contract.dual
        :en="'or its equivalent in Syrian pounds, to be paid in cash to the volunteer at the end of each month.'" 
        :ar="$Glyphs('أو ما يعادلها بالليرة السورية، تُمنح نقداً في نهاية كل شهر.')" />

</div> 

<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('8. Duties:')" :ar="$LB($Glyphs('8. المسؤوليات:'))" />
    <x-contract.dual class="mt-1"
        :en="'The second party commits to the description of the volunteer work approved by the first team. Therefore, their signature on this contract is an acknowledgment of their full commitment to it.'" 
        :ar="$Glyphs('يلتزم الفريق الثاني بتوصيف العمل التطوّعي الذي أقرّه الفريق الأول، وعليه فإن توقيعه على هذا العقد هو إقرار منه بالتزامه الكامل بها.')" />
</div>
