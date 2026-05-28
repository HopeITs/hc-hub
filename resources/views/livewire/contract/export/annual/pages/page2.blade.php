{{-- @php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;

    $pos = $contract->position;
    $req = $pos?->requiredPosition;

    $positionTitleEn = $req?->title_en ?? $pos?->title_en ?? ('#'.$contract->position_id);
    $positionTitleAr = $Glyphs($req?->title_ar ?? $pos?->title_ar ?? ('#'.$contract->position_id));

    // Dates (force English numbers)
    $startStr = $contract->start_date ? Carbon::parse($contract->start_date)->format('d/m/Y') : '—';
    $endStr   = $contract->end_date   ? Carbon::parse($contract->end_date)->format('d/m/Y')   : '—';

    $esc   = fn ($v) => e($v === '' || $v === null ? '—' : $v);
    $escAr = fn ($v) => e($v === '' || $v === null ? '—' : $Glyphs($v));

    $weeklyHours = (int) ($contract->weekly_hours ?? 40);
    $dailyHours = 8;
    $salary      = (float) ($contract->salary ?? 0);
    $salaryFormattedEn = $salary ? number_format($salary, 0, '.', ',') : '—';
    $salaryFormattedAr = $salary ? e(number_format($salary, 0, '.', ',')) : '—';
    $dailyStart = '9:30 AM';
    $dailyEnd = '5:30 PM';
    // Labels
    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';

    $wrapLtr = fn ($v) => '<span dir="ltr">'.$v.'</span>';
@endphp

<div class="avoid-break mt-4" style="line-height: 0.8">
    <x-contract.dual tight
                     :en="'Whereas Christian Hope Center Aleppo - Head office has agreed to this volunteering according to the conditions outlined in this contract,'"
                     :ar="$Glyphs(__('general.text11'))"
    />
    <x-contract.dual rowClass="mt-8"
                     :en="'This contract between the two parties witnesses mutual acceptance of the following:'"
                     :ar="$Glyphs(__('general.text12'))"
    />
</div>

<div class="avoid-break mt-6" style="line-height: 0.6">
    <x-contract.dual
        :en="$L('Contract type:')"
        :ar="$L($Glyphs(__('general.text13')))"
    />
    <x-contract.dual
        :en="'<strong> Volunteer agreement for limited period</strong>'"
        :ar="$Glyphs(__('general.text14'))"
    />
    <x-contract.dual
                     :en="$L('Place of Work:')"
                     :ar="$L($Glyphs(__('general.text15')))"
    />
    <x-contract.dual
                     :en="'<strong>Aleppo – Christian Hope Center – Head Office</strong>'"
                     :ar="$Glyphs(__('general.text16'))"
    />
    <x-contract.dual
                     :en="$L('Job description:')"
                     :ar="$L($Glyphs(__('general.text17')))"
    />
    <x-contract.dual
                     :en="$esc($positionTitleEn)"
                     :ar="$esc($positionTitleAr)"
    />
</div>

<div class="avoid-break mt-8" style="line-height: 0.6">
    <x-contract.dual
        :en="$L('Duration of the agreement:')"
        :ar="$L($Glyphs('مدة العقد:'))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="'One Year'"
                     :ar="$Glyphs('سنة')"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="'Starts from: '.$esc($startStr)"
                     :ar="$wrapLtr($esc($startStr)).' '.$Glyphs(__('general.text19'))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="'Ends on: '.$esc($endStr)"
                     :ar="$wrapLtr($esc($endStr)).' '.$Glyphs(__('general.text20'))"
    />
</div>

<div class="avoid-break mt-6" style="line-height: 0.7">
    <x-contract.dual
        :en="$L('Volunteer Work Hours:')"
        :ar="$L($Glyphs('ساعات العمل التطوعي:'))"
    />
    <x-contract.dual rowClass="mt-5"
                     :en="$weeklyHours.' working hours per week, 8 hours per day.'"
                     :ar="$Glyphs(__('general.text23')).' '.$dailyHours.' '.$Glyphs(__('general.text22')).' '.$weeklyHours"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="'Standard office hours are Monday to Friday.'"
                     :ar="$Glyphs(__('general.text24'))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="'from 9:30 AM to 5:30 PM.'"
                     :ar="$dailyEnd.' '.$Glyphs(__('general.text26')).' '.$dailyStart.$Glyphs(__('general.text25'))"
    />
    <x-contract.dual rowClass="mt-8"
                     :en="'Saturdays are considered working days when necessary and not treated as extra hours.'"
                     :ar="$Glyphs('يعتبر يوم السبت يوم عمل في حال رأت الإدارة الحاجة لذلك، ولا يعتبر دوام إضافي.')"
    />
</div>

<div class="avoid-break mt-4" style="line-height: 0.7">
    <x-contract.dual
        :en="$L('Stipend and Benefits:')"
        :ar="$L($Glyphs('المكافأة المالية والمزايا:'))"
    />
    <x-contract.dual
                     :en="'Christian Hope Center is to give a monthly stipend of / '.$salaryFormattedEn.' USD / or its equivalent in Syrian pounds, to be paid in cash to the volunteer at the end of each month.'"
                     :ar="$Glyphs(__('general.text35', ['salary' => $wrapLtr($salaryFormattedEn)]))"
    />

</div>

<div class="avoid-break mt-6" style="line-height: 0.7">
    <x-contract.dual
        :en="$L('Duties:')"
        :ar="$L($Glyphs('المسؤوليات:'))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="'As listed in the job description of the position (attached).'"
                     :ar="$Glyphs('حسب ما ورد في توصيف العمل التطوعي لهذا المنصب (مرفق).')"
    />
</div> --}}

@php
    use App\Support\ArabicText as AR;
    use Carbon\Carbon;

    $forPdf = $forPdf ?? false;
    $Glyphs = fn($s) => $forPdf ? AR::glyphs((string) $s) : (string) $s;
    $SmartGlyphs = fn($s, $limit = 2000) => $forPdf ? AR::smartGlyphs((string)$s, $limit) : (string)$s;
    $Digits = fn($s) => $forPdf ? AR::digits((string) $s) : (string) $s;

    $pos = $contract->position;
    $req = $pos?->requiredPosition;
    $positionTitleEn = $req?->title_en ?? ($pos?->title_en ?? '—');
    $positionTitleAr = $req?->title_ar ?? ($pos?->title_ar ?? '—');

    $startStr = $contract->start_date ? Carbon::parse($contract->start_date)->format('d/m/Y') : '—';
    $endStr = $contract->end_date ? Carbon::parse($contract->end_date)->format('d/m/Y') : '—';
    
    $startStrAr = $startStr;
    $endStrAr = $endStr;

    $salary = (float) ($contract->salary ?? 0);
    $salaryFormattedEn = $salary ? number_format($salary, 0, '.', ',') : '—';
    $salaryFormattedAr = $salary ? number_format($salary, 0, '.', ',') : '—';
    
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
        $durationLabelAr = $Glyphs('أشهر') . ' ' . $durationMonths;
    }

    $programId = $contract->position?->program_id;
    $isStudyZone  = ($programId == 8); 
    $isGeneralProject = !$isStudyZone;
@endphp

<div style="line-height: 0.9" >

    <div class="avoid-break mt-1" style="line-height: 0.7">
        <x-contract.dual :en="$LB('4. Job description:')" :ar="$LB($Glyphs('وصف العمل:') . ' .4')" />
        <x-contract.dual rowClass="mt-1 lh-tight" :en="'<strong>' . $esc($positionTitleEn) . '</strong>'" :ar="$SmartGlyphs('<strong>'.$esc($positionTitleAr).'</strong>')" />
    </div>

    <div class="avoid-break mt-1" style="line-height: 0.7">
        <x-contract.dual :en="$LB('5. Duration of the contract:')" :ar="$LB($Glyphs('مدة العقد:') . ' .5')" />
        <x-contract.dual rowClass="mt-1 lh-tight" :en="'<strong>' . $durationLabelEn . '</strong>'" :ar="'<strong>' . $durationLabelAr . '</strong>'" />

        <x-contract.dual rowClass="mt-1" :en="'Start date: <strong>' . $esc($startStr) . '</strong>'"
            :ar="$Glyphs('تبدأ من تاريخ: <strong>'.$esc($startStrAr).'</strong>')" />

        <x-contract.dual :en="'End date: <strong>' . $esc($endStr) . '</strong>'"
            :ar="$Glyphs('وتنتهي إلى تاريخ: <strong>'.$esc($endStrAr).'</strong>')" />
    </div>

    {{-- <div class="avoid-break">
        <x-contract.dual :en="$LB('6. Volunteer Work Hours:')" :ar="$LB($Glyphs('ساعات العمل التطوعّي:') . ' .6')" />
        
        <x-contract.dual class="mt-1"
            :en="'<strong>' . $weeklyHours . ' working hours</strong> per week, <strong>' . $dailyHours . ' hours</strong> per day.'" 
            :ar="$SmartGlyphs('<strong>' . $weeklyHours . ' ساعة</strong> عمل أسبوعياً، <strong>' . $dailyHours . ' ساعات</strong> يومياً.')" />
        
        <x-contract.dual class="mt-1" 
            :en="'Standard office hours are ' . $workDaysEn . ' from ' . $dailyStart . ' to ' . $dailyEnd . '.'"
            :ar="$SmartGlyphs('من نهار ' . $workDaysAr . ' من الساعة ' . $dailyStart . ' حتى الساعة ' . $dailyEnd . '.')" />

        @if($isGeneralProject)
            <x-contract.dual class="mt-1"
                :en="'Saturdays are considered working days when necessary and not treated as extra hours.'"
                :ar="$SmartGlyphs('يعتبر يوم السبت يوم عمل في حال رأت الإدارة الحاجة<br>لذلك، ولا يعتبر دوام إضافي.')"/>
        @endif

        @if($isStudyZone)
            <x-contract.dual class="mt-1"
                :en="'Volunteers are entitled to a second day off in the week, to be determined in coordination with management and based on work requirements. It is permitted to adjust working hours and holidays in months that require opening the center 7 days, in coordination with the direct manager and the HR officer.'"
                :ar="
                    $SmartGlyphs('يحق للمتطوع يوم عطلة ثان ضمن الأسبوع، يتم تحديده') . '<br>' .
                    $SmartGlyphs('بالتنسيق مع الإدارة وما يتطلبه العمل. يسمح بتعديل أوقات') . '<br>' .
                    $SmartGlyphs('الدوام والعطل في الأشهر التي تتطلب فتح المركز 7 أيام،') . '<br>' .
                    $SmartGlyphs('وذلك بالتنسيق مع المدير المباشر ومسؤول الموارد البشرية.')
                "/>
        @endif
    </div> --}}

    <div class="avoid-break mt-1" style="line-height: 0.7">
        <x-contract.dual 
            :en="$LB('6. Volunteer Work Hours:')" 
            :ar="$LB($Glyphs('ساعات العمل التطوعّي:') . ' .6')" />
        
        <x-contract.dual rowClass="mt-1 lh-tight"
            :en="'<strong>' . $weeklyHours . ' working hours</strong> per week, <strong>' . $dailyHours . ' hours</strong> per day.'" 
            :ar="$SmartGlyphs('ساعات يومياً.') . ' <strong>' . $dailyHours . '</strong> ' . $SmartGlyphs('ساعة عمل أسبوعياً،') . ' <strong>' . $weeklyHours . '</strong>'" />
        
<x-contract.dual rowClass="mt-1 lh-tight" 
            :en="'Standard office hours are ' . $workDaysEn . ' from ' . $dailyStart . ' to ' . $dailyEnd . '.'"
            
            :ar="
                $SmartGlyphs('من نهار ' . $workDaysAr) . '<br>' .
                '. ' . $wrapLtr($dailyEnd) . ' ' . $SmartGlyphs('حتى الساعة') . ' ' . $wrapLtr($dailyStart) . ' ' . $SmartGlyphs('من الساعة')
            " />
        @if($isGeneralProject)
            <x-contract.dual rowClass="mt-1 lh-tight"
                :en="'Saturdays are considered working days when necessary and not treated as extra hours.'"
                :ar="$SmartGlyphs('يعتبر يوم السبت يوم عمل في حال رأت الإدارة الحاجة<br>لذلك، ولا يعتبر دوام إضافي.')"/>
        @endif

        @if($isStudyZone)
            <x-contract.dual rowClass="mt-1 lh-tight"
                :en="'Volunteers are entitled to a second day off in the week, to be determined in coordination with management and based on work requirements. It is permitted to adjust working hours and holidays in months that require opening the center 7 days, in coordination with the direct manager and the HR officer.'"
                :ar="
                    $SmartGlyphs('يحق للمتطوع يوم عطلة ثان ضمن الأسبوع، يتم تحديده') . '<br>' .
                    $SmartGlyphs('بالتنسيق مع الإدارة وما يتطلبه العمل. يسمح بتعديل أوقات') . '<br>' .
                    $SmartGlyphs('الدوام والعطل في الأشهر التي تتطلب فتح المركز 7 أيام،') . '<br>' .
                    $SmartGlyphs('وذلك بالتنسيق مع المدير المباشر ومسؤول الموارد البشرية.')
                "/>
        @endif
    </div>

    <div class="avoid-break mt-1" style="line-height: 0.7">
        <x-contract.dual :en="$LB('7. Stipend and Benefits:')" :ar="$LB($Glyphs('المكافأة المالية والمزايا:') . ' .7')" />
        
        <x-contract.dual rowClass="mt-1 lh-tight"
            :en="'Christian Hope Center is to give a monthly stipend of<br><strong>/ ' . $salaryFormattedEn . ' USD /</strong>'" 
            :ar="$SmartGlyphs('خصص الفريق الأول للفريق الثاني شهرياً مكافأة وقدرها') . '<br><strong>/ ' . $Glyphs('دولار أمريكي') . ' ' . $salaryFormattedAr . ' /</strong>'" />

        <x-contract.dual :en="'or its equivalent in Syrian pounds, to be paid in cash to the volunteer at the end of each month.'" 
            :ar="$SmartGlyphs('أو ما يعادلها بالليرة السورية، تُمنح نقداً في نهاية كل شهر.')" />
    </div> 

    <div class="avoid-break" class="mt-1" style="line-height: 0.7">
        <x-contract.dual :en="$LB('8. Duties:')" :ar="$LB($Glyphs('المسؤوليات:') . ' .8')" />
        <x-contract.dual rowClass="mt-1 lh-tight"
            :en="'The second party commits to the description of the volunteer work approved by the first team. Therefore, their signature on this contract is an acknowledgment of their full commitment to it.'" 
            :ar="$SmartGlyphs('يلتزم الفريق الثاني بتوصيف العمل التطوّعي الذي أقرّه<br>الفريق الأول، وعليه فإن توقيعه على هذا العقد<br>هو إقرار منه بالتزامه الكامل بها.')" />
    </div>

    <div class="avoid-break" style="line-height: 0.7">
        <x-contract.dual :en="$LB('9. Annual Leave:')" :ar="$LB($Glyphs('إجازات العمل التطوعّي:') . ' .9')" />
        
        <x-contract.dual  rowClass="mt-1 lh-tight"
            :en="'The second team is entitled to <strong>' . $annualLeaveDays . '</strong> days of working days leave during the validity period of the contract, with it being proportional to the duration of the contract.'" 
            :ar="
                $SmartGlyphs('يوم إجازة سنوية خلال فترة سريان') . ' <strong>' . $annualLeaveDays . '</strong> ' . $SmartGlyphs('يحق للفريق الثاني') . '<br>' .
                $SmartGlyphs('العقد، وتكون متناسبة مع مدة العقد.')
            " />

        <x-contract.dual  rowClass="mt-1 lh-tight"
            :en="'The second team is entitled to 24 days of sick leave in cases of operations and infectious diseases, 12 days paid and 12 days paid at half the value of the monthly stipend, provided that a medical report is submitted to justify the leave.'" 
            :ar="
                $SmartGlyphs('يوم إجازة مرضية عند الضرورة فقط،') . ' 24 ' . $SmartGlyphs('يحق للفريق الثاني') . '<br>' .
                $SmartGlyphs('يوم مدفوع') . ' 12 ' . $SmartGlyphs('أي في حالات العمليات والأمراض المُعديَة،') . '<br>' .
                $SmartGlyphs('يوم مدفوع بنصف قيمة المكافأة المالية الشهرية وذلك') . ' 12' . $Glyphs('و') . '<br>' .
                $SmartGlyphs('بشرط تقديم تقرير طبي لتبرير الإجازة.')
            " />

        <x-contract.dual  rowClass="mt-1 lh-tight"
            :en="'- In case the second team consumes all annual and sickness leave days, they will be given 12 days of leave with the deduction of the number of leave days from the monthly stipend allowance.'" 
            :ar="
                $SmartGlyphs('في حال تجاوز الفريق الثاني أيام الاجازات المحددة له') . ' -<br>' .
                $SmartGlyphs('والتي تتناسب مع العقد سواءً إجازات عادية أو مرضية، يتم') . '<br>' .
                $SmartGlyphs('يوم يتم خصم قيمتها من') . ' 12 ' . $SmartGlyphs('منحه إجازة لا تتجاوز مدتها') . '<br>' .
                $SmartGlyphs('المكافأة المالية الشهرية المخصصة له.')
            " />
    </div>
 {{-- <div class="avoid-break mt-1" style="line-height: 0.6">
        <x-contract.dual :en="$LB('9. Annual Leave:')" :ar="$LB($Glyphs('إجازات العمل التطوعّي:') . ' .9')" />
        
        <x-contract.dual rowClass="mt-1"
            :en="'The second team is entitled to <strong>' . $annualLeaveDays . '</strong> days of working days leave during the validity period of the contract, with it being proportional to the duration of the contract. The second team is entitled to 24 days of sick leave in cases of operations and infectious diseases, 12 days paid and 12 days paid at half the value of the monthly stipend, provided that a medical report is submitted to justify the leave.'" 
:ar="
                $SmartGlyphs('يوم إجازة سنوية خلال فترة سريان') . ' <strong>' . $annualLeaveDays . '</strong> ' . $SmartGlyphs('يحق للفريق الثاني') . '<br>' .
                $SmartGlyphs('العقد، وتكون متناسبة مع مدة العقد.') . '<br>' .
                $SmartGlyphs('يوم إجازة مرضية عند الضرورة فقط،') . ' 24 ' . $SmartGlyphs('يحق للفريق الثاني') . '<br>' .
                $SmartGlyphs('يوم مدفوع') . ' 12 ' . $SmartGlyphs('أي في حالات العمليات والأمراض المُعديَة،') . '<br>' .
                $SmartGlyphs('يوم مدفوع بنصف قيمة المكافأة المالية الشهرية وذلك') . ' 12 ' . $Glyphs('و') . '<br>' .
                $SmartGlyphs('بشرط تقديم تقرير طبي لتبرير الإجازة.')
            " />

         <x-contract.dual rowClass="mt-1"
            :en="'- In case the second team consumes all annual and sickness leave days, they will be given 12 days of leave with the deduction of the number of leave days from the monthly stipend allowance.'" 
            :ar="
                $SmartGlyphs('في حال تجاوز الفريق الثاني أيام الاجازات المحددة له') . ' -<br>' .
                $SmartGlyphs('والتي تتناسب مع العقد سواءً إجازات عادية أو مرضية، يتم') . '<br>' .
                $SmartGlyphs('يوم يتم خصم قيمتها من') . ' 12 ' . $SmartGlyphs('منحه إجازة لا تتجاوز مدتها') . '<br>' .
                $SmartGlyphs('المكافأة المالية الشهرية المخصصة له.')
            " /> 
    </div> --}}


</div>