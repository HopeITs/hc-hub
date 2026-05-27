{{-- @php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;
    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $L      = fn (string $t) => '<span class="h-label">'.$t.'</span>';

    $start = $contract->start_date ? Carbon::parse($contract->start_date) : null;
    $end   = $contract->end_date   ? Carbon::parse($contract->end_date)   : null;

    $startEn = $start ? $start->format('d/m/Y') : '—';
    $endEn   = $end   ? $end->format('d/m/Y')   : '—';
    $startAr = $start ? ar_digits($start->format('d/m/Y')) : '—';
    $endAr   = $end   ? ar_digits($end->format('d/m/Y'))   : '—';

    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';

    // === Numbered list builder ===
    $asList = fn(array $lines, string $lang, int $start = 1) =>
        '<ul style="margin:0;padding:0;list-style:none;">' .
        implode('', array_map(function ($l, $i) use ($lang, $start) {
            $num = $start + $i;
            return $lang === 'ar'
                ? '<li style="direction:rtl;text-align:right;position:relative;padding-right:18px;padding-bottom:20px;">'
                    . '<span style="position:absolute;right:0;">.'.$num.'</span>'.$l.'</li>'
                : '<li style="position:relative;padding-left:16px;padding-bottom:14px;">'
                    . '<span style="position:absolute;left:0;">'.$num.'.</span>'.$l.'</li>';
        }, $lines, array_keys($lines))) .
        '</ul>';

    $EN_LIST_3 = [
        'The contract is considered valid <br> from  '.$startEn.' <Br> until '.$endEn.',<br>  and the first two months are considered a trial period for the second party where the first party is entitled to terminate the contract and not continue with the second party.<br><br>'
    ];

    $AR_LIST_3 = [
        $Glyphs(__('general.text45')).'<br>'.$startAr.' '.$Glyphs(__('general.text46')).'<br>'.$endAr.' '.$Glyphs(__('general.text47')).'<br>'.$Glyphs(__('general.text48')),
    ];
@endphp

<div class="avoid-break mt-8" style="line-height:0.9">
    <x-contract.dual :en="$L('First term:')" :ar="$L($Glyphs('البند الأول:'))"/>
    <x-contract.dual :en="'The introduction of this contract is considered an indissoluble part of it.'"
                     :ar="$Glyphs(__('general.text37'))"/>
</div>

<div class="avoid-break mt-8" style="line-height:0.9">
    <x-contract.dual :en="$L('Second term:')" :ar="$L($Glyphs('البند الثاني:'))"/>
    <x-contract.dual :en="'The second party is committed to implementing all volunteering activities agreed on with the first party and all requested programming services.'"
                     :ar="$Glyphs(__('general.text38'))"/>
</div>

<div class="avoid-break mt-8">
    <x-contract.dual :en="$L('Third term:')" :ar="$L($Glyphs('البند الثالث:'))"/>
</div>

<x-contract.dual rowClass="mt-6"
                 :en="$asList($EN_LIST_3, 'en', 1)"
                 :ar="$asList($AR_LIST_3, 'ar', 1)"/>
<div class="avoid-break mt-4">
    <x-contract.dual
        :en="'This contract will not be renewed without a written agreement of both parties, whereas each party shall inform the other of the contract termination one month prior to the end of the contract.'"
        :ar="        $Glyphs('لا يتجدد هذا العقد لمدة أخرى إلا بموافقة الفريقين <br>  خطياً، على أن يعلم كل فريق الآخر بعدم  <Br> التمديد قبل شهر من انتهاء العقد.')
"/>
</div>

<div class="avoid-break mt-8">
    <x-contract.dual
        :en="'Place of Work: Aleppo – Christian Hope Center – Head Office'"
        :ar="        $Glyphs('مكان العمل: حلب - مركز الرجاء المسيحي - المكتب الرئيسي')
"/>
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
    $dailyHours = $weeklyHours / 5;

    $workDaysEn = $contract->work_days_en ?: 'Monday to Friday';
    $workDaysAr = $contract->work_days_ar ?: 'الإثنين إلى الجمعة';

    $dailyStart = $contract->work_hours_from ? Carbon::parse($contract->work_hours_from)->format('g:i A') : '9:00 AM';
    $dailyEnd = $contract->work_hours_to ? Carbon::parse($contract->work_hours_to)->format('g:i A') : '5:00 PM';

    $esc = fn($v) => e((string) ($v ?: '—'));
    $escAr = fn($v) => e((string) ($v ? $Glyphs($v) : '—'));
    $LB = fn($t) => '<span class="h-label-bold">' . $t . '</span>';
    $wrapLtr = fn($v) => '<span dir="ltr">' . $v . '</span>';
    


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
    
    $asList = function(array $lines) {
        return '<div style="margin:0; padding:0;">'
            . implode('', array_map(fn($l) => '<div style="margin-top:2px;">'.$l.'</div>', $lines))
            . '</div>';
    };
@endphp



<div class="avoid-break mt-1">
    <x-contract.dual :en="$LB('4. Job description:')" :ar="$LB($Glyphs('وصف العمل:') . ' .4')" />

    <x-contract.dual class="mt-1" :en="'<strong>' . $esc($positionTitleEn) . '</strong>'" :ar="$SmartGlyphs('<strong>'.$esc($positionTitleAr).'</strong>')" />
</div>

<div class="avoid-break mt-1">
    <x-contract.dual :en="$LB('5. Duration of the contract:')" :ar="$LB($Glyphs('مدة العقد:') . ' .5')" />

        <x-contract.dual class="mt-1" 
        :en="'<strong>' . $durationLabelEn . '</strong>'" 
        :ar="'<strong>' . $durationLabelAr . '</strong>'" />

    <x-contract.dual class="mt-1" :en="'Start date: <strong>' . $esc($startStr) . '</strong>'"
        :ar="$Glyphs('تبدأ من تاريخ: <strong>'.$esc($startStrAr).'</strong>')" />

    <x-contract.dual :en="'End date: <strong>' . $esc($endStr) . '</strong>'"
        :ar="$Glyphs('وتنتهي إلى تاريخ: <strong>'.$esc($endStrAr).'</strong>')" />

    <x-contract.dual class="mt-1" :en="'The first 3 months are considered a probation period for the second party, in which the first party has the right to terminate the contract and not proceed with the second party.'" 
        :ar="$SmartGlyphs('ويعتبر أول ثلاثة أشهر فترة تجريبية للفريق الثاني، يحق<br>فيها للفريق الأول إنهاء العقد وعدم الاستمرار مع الفريق الثاني.')" />
</div>

<div class="avoid-break mt-1">
    <x-contract.dual 
        :en="$LB('6. Volunteer Work Hours:')" 
        :ar="$LB($Glyphs('ساعات العمل التطوعّي:') . ' .6')"
        />

    <x-contract.dual class="mt-1"
        :en="$weeklyHours . ' working hours per week, it changes according to the requirements of the job.'" 
        :ar="$Glyphs('ساعة عمل أسبوعياً، تتغير بحسب متطلّبات العمل.') . ' ' . $weeklyHours" />
</div>
<div class="avoid-break mt-1">
    <x-contract.dual :en="$LB('7. Stipend and Benefits:')" 
    :ar="$LB($Glyphs('المكافأة المالية والمزايا:') . ' .7')"
    />
    

        <x-contract.dual class="mt-1" :en="'Christian Hope Center is to give a monthly stipend of<br><strong>/ ' . $salaryFormattedEn . ' USD /</strong>'"
        :ar="$SmartGlyphs('خصص الفريق الأول للفريق الثاني شهرياً مكافأة وقدرها') . '<br><strong>/ ' . $Glyphs('دولار أمريكي') . ' ' . $salaryFormattedAr . ' /</strong>'" />

    <x-contract.dual :en="'or its equivalent in Syrian pounds, to be paid in cash to the volunteer at the end of each month.'" 
        :ar="$SmartGlyphs('أو ما يعادلها بالليرة السورية، تُمنح نقداً في نهاية كل شهر.')" />
</div>

<div class="avoid-break mt-1">
    <x-contract.dual :en="$LB('8. Duties:')" 
     :ar="$LB($Glyphs('المسؤوليات:') . ' .8')"
 />
    
    <x-contract.dual class="mt-1" :en="'The second party commits to the description of the volunteer work approved by the first team. Therefore, their signature on this contract is an acknowledgment of their full commitment to it.'" 
        :ar="$SmartGlyphs('يلتزم الفريق الثاني بتوصيف العمل التطوّعي الذي أقرّه<br>الفريق الأول، وعليه فإن توقيعه على هذا العقد<br>هو إقرار منه بالتزامه الكامل بها.')" />
</div>

<div class="avoid-break mt-1">
    <x-contract.dual 
        :en="$LB('9. Internal Rules and Regulations:')" 
       :ar="$LB($Glyphs('القوانين والأنظمة الداخلية:') . ' .9')" />
     
    <x-contract.dual class="mt-1"
        :en="'The second party acknowledges and pledges to commit to the following attached documents:'"
        :ar="$SmartGlyphs('يُقر الفريق الثاني ويتعهد بالاطلاع والالتزام<br>بالمستندات المرفقة التالية:')" />

@php
        $EN_RULES = ['- Job description.', '- Code of Conduct.', '- Christian Hope Center Policies.'];
        $AR_RULES = [
            $SmartGlyphs('توصيف العمل التطوّعي.') . ' -', 
            $SmartGlyphs('القواعد الأخلاقية للتطوّع.') . ' -', 
            $SmartGlyphs('سياسات مؤسسة مركز الرجاء المسيحي.') . ' -'
        ];
    @endphp
    <x-contract.dual class="mt-1" 
        :en="$asList($EN_RULES)" 
        :ar="$asList($AR_RULES)" />
</div>