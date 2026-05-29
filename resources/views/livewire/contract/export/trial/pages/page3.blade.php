{{-- @php
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
    :ar="$asList($AR_LIST_7,'ar',1)"/> --}}


@php
    use App\Support\ArabicText as AR;
    $forPdf = $forPdf ?? false;
    $Glyphs = fn($s) => $forPdf ? AR::glyphs((string) $s) : (string) $s;
    $SmartGlyphs = fn($s, $limit = 2000) => $forPdf ? AR::smartGlyphs((string) $s, $limit) : (string) $s;
    $LB = fn($t) => '<span class="h-label-bold">' . $t . '</span>';

    $asList = function (array $lines) {
        return '<div style="margin:0; padding:0;">' .
            implode('', array_map(fn($l) => '<div style="margin-top:2px;">' . $l . '</div>', $lines)) .
            '</div>';
    };

    $arBullet = function (array $lines) use ($SmartGlyphs) {
        $html = '';
        $count = count($lines);
        foreach ($lines as $i => $line) {
            $processed = $SmartGlyphs($line);
            if ($i === 0) {
                $html .= $processed . ' -';
            } else {
                $html .= $processed;
            }
            if ($i < $count - 1) {
                $html .= '<br>';
            }
        }
        return $html;
    };

    // --- اقتراحك الممتاز: تعريف المتغيرات هنا لترتيب الأرقام والأسطر ---

    $leaveRule1Ar =
        $SmartGlyphs('لا يحق للفريق الثاني إجازات خلال فترة سريان العقد') . ' -<br>' . $SmartGlyphs('التجريبي.');

    // تركيب السطر: (اليسار: أيام متتالية...) + 3 + (اليمين: أكثر من)
    $leaveRule2Ar =
        $SmartGlyphs('في حال اضطرّار الفريق الثاني للغياب فعليه أخذ') .
        ' -<br>' .
        $SmartGlyphs('موافقة الفريق الأول بذلك على ألا يتجاوز الأمر') .
        '<br>' .
        $SmartGlyphs('أيام متتالية خلال فترة العقد، وذلك') .
        ' 3 ' .
        $SmartGlyphs('أكثر من') .
        '<br>' .
        $SmartGlyphs('ما عدا حالات الوفاة والمرض.');

    // تركيب السطر: (اليسار: مرات في الشهر.) + 3 + (اليمين: موعد الدوام المحدد لأكثر من)
    $leaveRule3Ar =
        $SmartGlyphs('يُخصم يوم عمل تطوّّعي للفريق الثاني من مكافأة') .
        ' -<br>' .
        $SmartGlyphs('التطوّّع المخصصة له في حال تكرار التأخير عن') .
        '<br>' .
        $SmartGlyphs('مرات في الشهر.') .
        ' 3 ' .
        $SmartGlyphs('موعد الدوام المحدد لأكثر من');
@endphp



{{-- <div class="avoid-break" style="margin-top: 10px;">
        <x-contract.dual rowClass="lh-tight" :en="$LB('9. Annual Leave:')" :ar="$LB($Glyphs('إجازات العمل التطوعّي:') . ' .9')" />
        
        <x-contract.dual rowClass="mt-1 lh-tight"
            :en="'- The second party is not entitled to any leave days during the period of the trial contract.'" 
            :ar="$SmartGlyphs('لا يحق للفريق الثاني إجازات خلال فترة سريان العقد التجريبي.') . ' -'" />

        <x-contract.dual rowClass="mt-1 lh-tight"
            :en="'- If the second party has a strong reason to be absent, they must inform the first party, as the leave shall not exceed 3 days in a row during the contract period, except in cases of death or severe sickness.'" 
            :ar="
                $SmartGlyphs('في حال اضطرّار الفريق الثاني للغياب فعليه أخذ موافقة الفريق') . ' -<br>' .
                $SmartGlyphs('الأول بذلك على ألا يتجاوز الأمر أكثر من 3 أيام متتالية') . '<br>' .
                $SmartGlyphs('خلال فترة العقد، وذلك ما عدا حالات الوفاة والمرض.')
            " />

        <x-contract.dual rowClass="mt-1 lh-tight"
            :en="'- The second party stipend amount will be deducted for one working day in case of repeated delays beyond the specified working hours more than 3 times a month.'" 
            :ar="
                $SmartGlyphs('يُخصم يوم عمل تطوّّعي للفريق الثاني من مكافأة التطوّّع المخصصة') . ' -<br>' .
                $SmartGlyphs('له في حال تكرار التأخير عن موعد الدوام المحدد لأكثر من 3 مرات في الشهر.')
            " />
    </div> --}}

<div class="avoid-break mt-2">
    <x-contract.dual rowClass="lh-tight" :en="$LB('9. Annual Leave:')" :ar="$LB($Glyphs('إجازات العمل التطوعّي:') . ' .9')" />

    <x-contract.dual rowClass="mt-1 lh-tight" :en="'- The second party is not entitled to any leave days during the period of the trial contract.'" :ar="$leaveRule1Ar" />

    <x-contract.dual rowClass="mt-1 lh-tight" :en="'- If the second party has a strong reason to be absent, they must inform the first party, as the leave shall not exceed 3 days in a row during the contract period, except in cases of death or severe sickness.'" :ar="$leaveRule2Ar" />

    <x-contract.dual rowClass="mt-1 lh-tight" :en="'- The second party stipend amount will be deducted for one working day in case of repeated delays beyond the specified working hours more than 3 times a month.'" :ar="$leaveRule3Ar" />
</div>

<div class="avoid-break" style="margin-top: 15px;">
    <x-contract.dual rowClass="lh-tight" :en="$LB('10. Internal Rules and Regulations:')" :ar="$LB($Glyphs('القوانين والأنظمة الداخلية:') . ' .10')" />

    <x-contract.dual rowClass="mt-1 lh-tight" :en="'The second party acknowledges and pledges to commit to the following attached documents:'" :ar="$SmartGlyphs('يُقر الفريق الثاني ويتعهد بالاطلاع والالتزام<br>بالمستندات المرفقة التالية:')" />

    @php
        $EN_RULES = ['- Job description.', '- Code of Conduct.', '- Christian Hope Center Policies.'];
        $AR_RULES = [
            $SmartGlyphs('توصيف العمل التطوّعي.') . ' -',
            $SmartGlyphs('القواعد الأخلاقية للتطوّع.') . ' -',
            $SmartGlyphs('سياسات مؤسسة مركز الرجاء المسيحي.') . ' -',
        ];
    @endphp
    <x-contract.dual rowClass="mt-1 lh-tight" :en="$asList($EN_RULES)" :ar="$asList($AR_RULES)" />
</div>

{{-- <div class="avoid-break" style="margin-top: 15px;">
        <x-contract.dual rowClass="lh-tight" :en="$LB('11. Termination of the Contract:')" :ar="$LB($Glyphs('إنهاء العقد:') . ' .11')" />
        @php
            $EN_TERM = [
                '- The first party has the right to terminate this contract at any time before its expiry date in case the second party violates the terms of this contract and the volunteering code of conduct.',
                '- If the second party wishes to terminate the contract, the second party is obliged to inform the first party at least a Two week before the end of the contract period.',
                '- The second party is committed to hand over to "Christian Hope Center" all the equipment, material, and documents that they received upon the termination or expiration of this contract, based on the personal receipt list of the volunteer.'
            ];
            $AR_TERM = [
                $arBullet([
                    'يحق للفريق الأول إنهاء هذا العقد في أي وقت قبل إنتهاء مدته',
                    'في حال قيام الفريق الثاني بمخالفة بنود هذا العقد والمستندات المرفقة به.'
                ]),
                $arBullet([
                    'في حال رغبة الفريق الثاني بفسخ العقد، يلتزم بأن يُعلم الفريق الأول',
                    'قبل أسبوعين على الأقل من تاريخ انتهاء العقد.'
                ]),
                $arBullet([
                    'يلتزم الفريق الثاني عند انتهاء أو إنهاء العقد بتسليم جميع التجهيزات',
                    'والمواد والوثائق المؤتمن بها للفريق الأول وذلك استناداً لقائمة الاستلام الشخصي.'
                ])
            ];
        @endphp
        <x-contract.dual rowClass="mt-1 lh-tight" :en="$asList($EN_TERM)" :ar="$asList($AR_TERM)" />
    </div> --}}

<div class="avoid-break mt-2">
    <x-contract.dual rowClass="lh-tight" :en="$LB('11. Termination of the Contract:')" :ar="$LB($Glyphs('إنهاء العقد:') . ' .11')" />
    @php
        $EN_TERM = [
            '- The first party has the right to terminate this contract at any time before its expiry date in case the second party violates the terms of this contract and the volunteering code of conduct.',
            '- If the second party wishes to terminate the contract, the second party is obliged to inform the first party at least a Two week before the end of the contract period.',
            '- The second party is committed to hand over to "Christian Hope Center" all the equipment, material, and documents that they received upon the termination or expiration of this contract, based on the personal receipt list of the volunteer.',
        ];
        $AR_TERM = [
            $arBullet([
                'يحق للفريق الأول إنهاء هذا العقد في أي وقت قبل',
                'إنتهاء مدته في حال قيام الفريق الثاني بمخالفة بنود',
                'هذا العقد والمستندات المرفقة به.',
            ]),
            $arBullet([
                'في حال رغبة الفريق الثاني بفسخ العقد، يلتزم بأن',
                'يُعلم الفريق الأول قبل أسبوعين على الأقل من تاريخ',
                'انتهاء العقد.',
            ]),
            $arBullet([
                'يلتزم الفريق الثاني عند انتهاء أو إنهاء العقد',
                'بتسليم جميع التجهيزات والمواد والوثائق المؤتمن بها',
                'للفريق الأول وذلك استناداً لقائمة الاستلام الشخصي.',
            ]),
        ];
    @endphp
    {{-- <x-contract.dual rowClass="mt-1 lh-tight" :en="$asList($EN_TERM)" :ar="$asList($AR_TERM)" /> --}}
            @foreach ($EN_TERM as $index => $enItem)
        <x-contract.dual class="mt-1" :en="$enItem" :ar="$AR_TERM[$index]" />
    @endforeach
</div>

<div class="avoid-break mt-2">
    <x-contract.dual rowClass="lh-tight" :en="$LB('12. Special conditions:')" :ar="$LB($Glyphs('شروط خاصة:') . ' .12')" />
    <x-contract.dual rowClass="mt-1 lh-tight" :en="'The second team commits to maintaining the confidentiality of all information and documents they have access to, and not using them outside the scope of the volunteer work. The first team has the right to take legal action against the second team in case of a breach of this clause.'" :ar="$SmartGlyphs('يتعهد الفريق الثاني بالحفاظ على سريّة جميع المعلومات') .
        '<br>' .
        $SmartGlyphs('والوثائق التي يطّلّع عليها، وعدم استخدامها خارج نطاق العمل') .
        '<br>' .
        $SmartGlyphs('التطوّعي. ويحق للفريق الأول مقاضاة الفريق الثاني في حال') .
        '<br>' .
        $SmartGlyphs('إخلاله بهذا البند.')" />
</div>
