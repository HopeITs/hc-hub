@php
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;
    $Glyphs = fn($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $Digits = fn($s) => $forPdf ? ar_digits((string)$s) : (string)$s;
    $L = fn($t) => '<span class="h-label">'.$t.'</span>';
    $wrapLtr = fn($v)=>'<span dir="ltr">'.$v.'</span>';

    $salary = (float)($contract->salary ?? 0);
    $salaryFormattedEn = $salary ? number_format($salary,0,'.',',') : '—';

    // reusable list builder with offset start
    $asList = fn(array $lines,string $lang,int $start=1)=>
        '<ul style="margin:0;padding:0;list-style:none;">'.
        implode('',array_map(function($l,$i)use($lang,$start){
            $num=$start+$i;
            return $lang==='ar'
                ? '<li style="direction:rtl;text-align:right;position:relative;padding-right:18px;padding-bottom:40px;"><span style="position:absolute;right:0;">.'.$num.'</span>'.$l.'</li>'
                : '<li style="position:relative;padding-left:16px;padding-bottom:14px;"><span style="position:absolute;left:0;">'.$num.'.</span>'.$l.'</li>';
        },$lines,array_keys($lines))).
        '</ul>';

    // Sixth term (continues numbering from previous page)
    $EN_LIST_6 = [
        'The first party is committed to paying the second party in exchange for his/her services, a monthly stipend of / '.$salaryFormattedEn.' USD only/.<br><br>',
        'The second party has to commit, abide and sign the Code of Conduct.',
    ];
    $AR_LIST_6 = [
        $Glyphs(__('general.text44',['salary'=>$wrapLtr($salaryFormattedEn)])),
        $Glyphs(__('general.text53')),
    ];

    // Seventh term (reset numbering to 1–4)
    $EN_LIST_7 = [
        'The Second party stipend amount will be deducted an amount of one working day in case of repeated delays beyond the specified working hours for more than 3 times a month.',
        'The volunteer is not entitled to any leave days during the period of trial contract.<br><br>',
        'If the second party has a strong reason to be absent, he/she must inform the first party, without exceeding 3 days in a row during the contract period with exception of cases regarding death or severe sickness.',
        'The first party has the right to terminate this contract at any time before its expiry date in case the second party violates the terms of this contract and the volunteering code of conduct, in which case all due amounts must be paid to the second party up to the termination date.',
    ];
    $AR_LIST_7 = [
        $Glyphs('يخصم يوم عمل تطوعي للفريق الثاني من المكافأة المالية المخصصة له في حال تكرار التأخير عن موعد الدوام المحدد لأكثر من 3 مرات في الشهر.'),
        $Glyphs('لا يحق للمتطوع إجازات خلال فترة سريان العقد التجريبي.'),
        $Glyphs(__('general.text54')),
        $Glyphs('يحق للفريق الأول إنهاء العقد في أي وقت قبل انتهاء مدته في حال قيام الفريق الثاني بمخالفة بنود هذا العقد والقواعد الأخلاقية للتطوع، وعليه في هذه  <Br> الحالة يجب أن يدفع للفريق الثاني كافة <Br> المبالغ المستحقة حتى تاريخ الإنهاء.'),
    ];
@endphp


<div class="avoid-break mt-4">
    <x-contract.dual :en="$L('Fourth term:')" :ar="$L($Glyphs('البند الرابع:'))"/>
</div>

<x-contract.dual rowClass="mt-6"
                 :en="$asList($EN_LIST_6,'en',6)"
                 :ar="$asList($AR_LIST_6,'ar',6)"/>

<div class="avoid-break">
    <x-contract.dual :en="$L('Fifth term:')" :ar="$L($Glyphs('البند الخامس:'))"/>
</div>

<x-contract.dual class=""
    :en="$asList($EN_LIST_7,'en',1)"
    :ar="$asList($AR_LIST_7,'ar',1)"/>
