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
    $endStrAr = $Digits($endStr);
    $annualLeaveDays = $contract->leaveBalances->first()?->int_total ?? '—';

    $esc = fn($v) => e((string) ($v ?: '—'));
    $escAr = fn($v) => e((string) ($v ? $Glyphs($v) : '—'));
    $LB = fn($t) => '<span class="h-label-bold">' . $t . '</span>';
    $wrapLtr = fn($v) => '<span dir="ltr">' . $v . '</span>';
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
        $asList = function(array $lines) {
        return '<div style="margin:0; padding:0;">'
            . implode('', array_map(fn($l) => '<div style="margin-top:2px;">'.$l.'</div>', $lines))
            . '</div>';
    };
@endphp


<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('4. Job description:')" :ar="$LB($Glyphs('4. وصف العمل:'))" />

    <x-contract.dual class="mt-1" :en="'<strong>' . $esc($positionTitleEn) . '</strong>'" :ar="'<strong>'.$escAr($positionTitleAr).
    '</strong>'" />
</div>
<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('5. Duration of the contract:')" :ar="$LB($Glyphs('5. مدة العقد:'))" />

    <x-contract.dual class="mt-1" :en="'<strong>' . $durationLabelEn . '</strong>'" :ar="'<strong>'.$durationLabelAr.
    '</strong>'" />

    <x-contract.dual class="mt-1" :en="'Start date: <strong>' . $esc($startStr) . '</strong>'"
        :ar="$Glyphs('تبدأ من تاريخ:').
        ' <strong>'.$wrapLtr($esc($startStrAr)).
        '</strong>'" />

    <x-contract.dual :en="'End date: <strong>' . $esc($endStr) . '</strong>'"
        :ar="$Glyphs('وتنتهي إلى تاريخ:').
        ' <strong>'.$wrapLtr($esc($endStrAr)).
        '</strong>'" />


    <x-contract.dual class="mt-1" :en="'The first 3 months are considered a probation
    period for the second party, in which the first
    party has the right to terminate the contract
    and not proceed with the second party.'" :ar="$Glyphs('ويعتبر أول ثلاثة أشهر فترة تجريبية للفريق الثاني،
    يحق فيها للفريق الأول إنهاء العقد وعدم الاستمرار
    مع الفريق الثاني.')" />
</div>



<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('6. Volunteer Work Hours:')" :ar="$LB($Glyphs('6. ساعات العمل التطوعّي:'))" />

    <x-contract.schedule-clause :contract="$contract" :for-pdf="$forPdf" />
</div>

<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('7. Stipend and Benefits:')" :ar="$LB($Glyphs('7. المكافأة المالية والمزايا:'))" />

    <x-contract.pay-clause :contract="$contract" :for-pdf="$forPdf" />
</div>

<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('8. Duties:')" :ar="$LB($Glyphs('8. المسؤوليات:'))" />
    <x-contract.dual class="mt-1" :en="'The second party commits to the description of the volunteer work approved by the first team. Therefore, their signature on this contract is an acknowledgment of their full commitment to it.'" :ar="$Glyphs(
        'يلتزم الفريق الثاني بتوصيف العمل التطوّعي الذي أقرّه الفريق الأول، وعليه فإن توقيعه على هذا العقد هو إقرار منه بالتزامه الكامل بها.',
    )" />
</div>


<div class="avoid-break mt-1">
    <x-contract.dual 
        :en="$LB('9. Internal Rules and Regulations:')" 
        :ar="$LB($Glyphs('9. القوانين والأنظمة الداخلية:'))" />
    
    <x-contract.dual class="mt-1"
        :en="'The second party acknowledges and pledges to commit to the following attached documents:'"
        :ar="$Glyphs('الفريق الثاني يُقر ويتعهد بالاطلاع والالتزام بالمستندات المرفقة التالية:')" />

    @php
        $EN_RULES = ['- Job description.', '- Code of Conduct.', '- Christian Hope Center Policies.'];
        $AR_RULES = [$Glyphs('- توصيف العمل التطوّعي.'), $Glyphs('- القواعد الأخلاقية للتطوّع.'), $Glyphs('- سياسات مؤسسة مركز الرجاء المسيحي.')];
    @endphp
    <x-contract.dual class="mt-1" 
        :en="$asList($EN_RULES)" 
        :ar="$asList($AR_RULES)" />
</div>
