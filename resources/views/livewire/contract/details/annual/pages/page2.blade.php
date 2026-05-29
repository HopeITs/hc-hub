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
    $dailyHours  = $weeklyHours / 5; 

    $workDaysEn = $contract->work_days_en ?: 'Monday to Friday';
    $workDaysAr = $contract->work_days_ar ?: 'الإثنين إلى الجمعة';

    $dailyStart = $contract->work_hours_from ? Carbon::parse($contract->work_hours_from)->format('g:i A') : '9:00 AM';
    $dailyEnd   = $contract->work_hours_to ? Carbon::parse($contract->work_hours_to)->format('g:i A') : '5:00 PM';

    $esc = fn($v) => e((string) ($v ?: '—'));
    $escAr = fn($v) => e((string) ($v ? $Glyphs($v) : '—'));
    $LB = fn($t) => '<span class="h-label-bold">' . $t . '</span>';
    $wrapLtr = fn ($v) => '<span dir="ltr">'.$v.'</span>';
    // 
$durationMonths = (int) round($contract->duration);
    
    if ($durationMonths == 12) {
        $durationLabelEn = 'One Year';
        $durationLabelAr = $Glyphs('سنة واحدة');
    } elseif ($durationMonths == 1) {
        $durationLabelEn = 'One Month';
        $durationLabelAr = $Glyphs('شهر واحد');
    } elseif ($durationMonths == 2) {
        $durationLabelEn = 'Two Months';
        $durationLabelAr = $Glyphs('شهرين');
    } else {
        $durationLabelEn = $durationMonths . ' Months';
        $durationLabelAr = $Digits($durationMonths) . ' ' . $Glyphs('أشهر');
    }
    $programId = $contract->position?->program_id;

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
        :ar="$LB($Glyphs('5. مدة العقد:'))" />

    <x-contract.dual class="mt-1"
        :en="'<strong>' . $durationLabelEn . '</strong>'" 
        :ar="'<strong>' . $durationLabelAr . '</strong>'" />

    <x-contract.dual class="mt-1"
        :en="'Start date: <strong>' . $esc($startStr) . '</strong>'" 
        :ar="$Glyphs('تبدأ من تاريخ:') . ' <strong>' . $wrapLtr($esc($startStrAr)) . '</strong>'" />

    <x-contract.dual 
        :en="'End date: <strong>' . $esc($endStr) . '</strong>'" 
        :ar="$Glyphs('وتنتهي إلى تاريخ:') . ' <strong>' . $wrapLtr($esc($endStrAr)) . '</strong>'" />
</div> 



<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('6. Volunteer Work Hours:')" :ar="$LB($Glyphs('6. ساعات العمل التطوعّي:'))" />

    
        
             <x-contract.dual class="mt-1"
            :en="'<strong>' . $weeklyHours . ' working hours</strong> per week, <strong>' . $dailyHours . ' hours</strong> per day.'" 
            :ar="'<strong>' . $Digits($weeklyHours) . ' ' . $Glyphs('ساعة') . '</strong> ' . $Glyphs('عمل أسبوعياً،') . ' <strong>' . $Digits($dailyHours) . ' ' . $Glyphs('ساعات') . '</strong> ' . $Glyphs('يومياً.')" />
        <x-contract.dual class="mt-1" 
            :en="'Standard office hours are ' . $workDaysEn . ' from ' . $dailyStart . ' to ' . $dailyEnd . '.'"
            :ar="$Glyphs('من نهار ') . $Glyphs($workDaysAr) . ' ' . $Glyphs('من الساعة ') . $dailyStart . ' ' . $Glyphs('حتى الساعة ') . $dailyEnd . '.'" />

        @if($isGeneralProject)
            <x-contract.dual class="mt-1"
                :en="'Saturdays are considered working days when necessary and not treated as extra hours.'"
                :ar="$Glyphs('يعتبر يوم السبت يوم عمل في حال رأت الإدارة الحاجة لذلك، ولا يعتبر دوام إضافي.')"/>
        @endif

        @if($isStudyZone)
            <x-contract.dual class="mt-1"
                :en="'Volunteers are entitled to a second day off in the week, to be determined in coordination with management and based on work requirements. It is permitted to adjust working hours and holidays in months that require opening the center 7 days, in coordination with the direct manager and the HR officer.'"
                :ar="$Glyphs('يحق للمتطوع يوم عطلة ثان ضمن الأسبوع، يتم تحديده بالتنسيق مع الإدارة وما يتطلبه العمل. يسمح بتعديل أوقات الدوام والعطل في الأشهر التي تتطلب فتح المركز 7 أيام، وذلك بالتنسيق مع المدير المباشر ومسؤول الموارد البشرية.')"/>
        @endif
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

