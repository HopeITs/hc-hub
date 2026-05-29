{{-- @php
    use Illuminate\Support\Number;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;
    $Glyphs = fn($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $L      = fn($t) => '<span class="h-label">'.$t.'</span>';

    $esc = fn($v) => e($v === '' || $v === null ? '—' : $v);
    $position = $contract->position;
    $program  = $position?->program->name ?? '—';
    $positionEN = $position?->title ?? '—';
    $positionAR = $position?->title_ar ?? '—';
    $placeEn = $program;
    $placeAr = $program;
    $dailyStart = '9:30 AM';
    $dailyEnd   = '5:30 PM';
    $asList = fn(array $lines, string $lang, int $start = 1) =>
        '<ul style="margin:0;padding:0;list-style:none;">' .
        implode('', array_map(function ($l, $i) use ($lang, $start) {
            $num = $start + $i;
            return $lang === 'ar'
                ? '<li style="direction:rtl;text-align:right;position:relative;padding-right:18px;padding-bottom:20px;">'
                    . '<span style="position:absolute;right:0;">.'.$num.'</span>'.$l.'</li>'
                : '<li style="position:relative;padding-left:16px;padding-bottom:20px;">'
                    . '<span style="position:absolute;left:0;">'.$num.'.</span>'.$l.'</li>';
        }, $lines, array_keys($lines))) .
        '</ul>';

    $t = function ($model, string $field, string $lang, $fallback = null) {
        if (! $model) return $fallback;
        if (method_exists($model, 'getTranslation')) {
            $v = $model->getTranslation($field, $lang);
            return ($v === null || $v === '') ? $fallback : $v;
        }
        $raw = $model->{$field} ?? null;
        if (is_array($raw)) {
            $v = $raw[$lang] ?? reset($raw);
            return ($v === null || $v === '') ? $fallback : $v;
        }
        return ($raw === null || $raw === '') ? $fallback : $raw;
    };

    $roleEn = $t($contract->position?->requiredPosition, 'title', 'en',
         $t($contract->position, 'title', 'en', 'Web Developer'));
    $roleAr = $Glyphs($t($contract->position?->requiredPosition, 'title', 'ar',
         $t($contract->position, 'title', 'ar', 'مطوّر ويب')));

    $amount = $contract->salary ?? null;
    $amtEn = is_numeric($amount) ? Number::format($amount, locale: 'en') : '—';
    $amtAr = is_numeric($amount) ? ar_digits(Number::format($amount, locale: 'en')) : '—';

    $wrapLtr = fn($v)=>'<span dir="ltr">'.$v.'</span>';
    $salary = (float)($contract->salary ?? 0);
    $salaryFormattedEn = $salary ? number_format($salary,0,'.',',') : '—';

    $EN_LIST_3 = [
        'The second party is committed to delivering volunteering services as a <strong>'.$esc($positionEN).'</strong>. for 6 days per week,6 hours a day (Working Hours are determined in coordination with the direct manager)',
        'The second party is committed to delivering all equipment, materials, and documents he/she received to Hope Center immediately after the termination of this contract in accordance with the receive list of each volunteer which is regularly updated.',
        "The first party is committed to paying the second party in exchange for volunteering services a monthly stipend of {$amtEn} SYP / Syrian Pounds only, to be paid in cash to the volunteer at the end of each month.",
        'The second party has to commit, abide and sign the code of conduct.',
    ];

    $AR_LIST_3 = [
        $Glyphs(__('general.text41',['position'=>$positionAR])).'<Br>'.$Glyphs(__('general.text50')).' 6'.$Glyphs(__('general.text49')).' 6'.'<br>'.$Glyphs(__('general.text51')).'<br><Br>',
        $Glyphs('يلتزم الفريق الثاني بتسليم جميع التجهيزات والمواد والوثائق التي استلمها فور انتهاء هذا العقد إلى مركز الرجاء، وذلك استناداً إلى لائحة الاستلام <Br> الخاصة بكل متطوع والتي يتم تحديثها دورياً.').'<Br><Br><Br>',
        $Glyphs(__('general.text44',['salary'=>$wrapLtr($salaryFormattedEn)])).'<br>'.$Glyphs(__('general.text52')).'<br><Br>',
        $Glyphs('على الفريق الثاني الامتثال للقواعد الأخلاقية للتطوع والتقيد بها وتوقيعها.'),
    ];

    $EN_LIST_4 = [
        'The second party stipend amount will be deducted in case of repeated delays beyond work hours for more than 3 times a month, an amount equal to one work day cost.',
        'If the second party has a strong reason to be absent, he/she must obtain the first party’s approval.',
    ];

    $AR_LIST_4 = [
        $Glyphs('يُحسم للفريق الثاني من المكافأة المالية المخصصة <br> له في حال  تكرار التأخير عن مواعيد  الدوام <br> المحددة لأكثر من 3 مرات في الشهر، <br> مبلغاً يعادل قيمة يوم دوام.'),
        $Glyphs('في حال اضطرار الفريق الثاني للغياب، فعليه أخذ <Br> موافقة الفريق الأول بذلك.'),
    ];
@endphp

<div class="avoid-break mt-8">
    <x-contract.dual :en="$L('Third term:')" :ar="$L($Glyphs('البند الثالث:'))"/>
</div>

<x-contract.dual
    rowClass="mt-6"
    :en="$asList($EN_LIST_3, 'en', 2)"
    :ar="$asList($AR_LIST_3, 'ar', 2)"
/>

<div class="avoid-break mt-4">
    <x-contract.dual :en="$L('Fourth term:')" :ar="$L($Glyphs('البند الرابع:'))"/>
</div>

<x-contract.dual
    rowClass="mt-6"
    :en="$asList($EN_LIST_4, 'en', 1)"
    :ar="$asList($AR_LIST_4, 'ar', 1)"
/> --}}

@php
    use App\Support\ArabicText as AR;
    
    $forPdf = $forPdf ?? false;
    $Glyphs = fn($s) => $forPdf ? AR::glyphs((string) $s) : (string) $s;
    $SmartGlyphs = fn($s, $limit = 2000) => $forPdf ? AR::smartGlyphs((string)$s, $limit) : (string)$s;
    $LB = fn($t) => '<span class="h-label-bold">' . $t . '</span>';
    
    $asList = function (array $lines) {
        return '<div style="margin:0; padding:0;">' .
            implode('', array_map(fn($l) => '<div style="margin-top:2px;">' . $l . '</div>', $lines)) .
            '</div>';
    };

    $arBullet = function(array $lines) use ($SmartGlyphs) {
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
@endphp

<div class="avoid-break mt-4">
    <x-contract.dual 
        :en="$LB('10. Termination of the Contract:')" 
        :ar="$LB($Glyphs('إنهاء العقد:') . ' .10')" />
    
    @php
        $EN_TERM = [
            '- The first party has the right to terminate this contract at any time before its expiry date in case the second party violates the terms of this contract and the volunteering code of conduct, or in the case of Unsatisfactory results of job performance.',
            '- This contract will not be renewed without a written agreement of both parties, whereas each party shall inform the other of the contract termination one month prior to the end of the contract.',
            '- The second party is committed to hand over to “Christian Hope Center” all the equipment, material, and documents that they received upon the termination or expiration of this contract, based on the personal receipt list of the volunteer.',
        ];
        
        $AR_TERM = [
$arBullet([
                'يحق للفريق الأول إنهاء هذا العقد في أي وقت قبل',
                'انتهاء مدته في حال قيام الفريق الثاني بمخالفة بنود',
                'هذا العقد والمستندات المرفقة به أو في حال نتائج',
                'تقييم الأداء غير الجيدة.'
            ]),
            $arBullet([
                'لا يتجدد هذا العقد لمدة أخرى إلا بموافقة الفريقين',
                'خطياً، على أن يُعلم كل فريق الآخر بعدم التجديد قبل',
                'شهر من انتهاء العقد، أو قبل شهر من تاريخ الرغبة',
                'بفسخ العقد.'
            ]),
            $arBullet([
                'يلتزم الفريق الثاني عند انتهاء أو إنهاء العقد بتسليم',
                'جميع التجهيزات والمواد والوثائق المؤتمن بها للفريق',
                'الأول وذلك استناداً لقائمة الاستلام الشخصي.'
            ])
        ];
    @endphp
    {{-- <x-contract.dual class="mt-1" :en="$asList($EN_TERM)" :ar="$asList($AR_TERM)" /> --}}

            @foreach ($EN_TERM as $index => $enItem)
        <x-contract.dual class="mt-1" :en="$enItem" :ar="$AR_TERM[$index]" />
    @endforeach
</div>

<div class="avoid-break mt-4">
    <x-contract.dual 
        :en="$LB('11. Special conditions:')" 
        :ar="$LB($Glyphs('شروط خاصة:') . ' .11')" />

    @php
        $EN_SPECIAL = [
            '- If the second party has a strong purpose to be absent, they must inform the first party.',
            '- The second party stipend amount will be deducted for one working day in case of repeated delays beyond the specified working hours more than 3 times a month.',
            '- The second team commits to maintaining the confidentiality of all information and documents they have access to, and not using them outside the scope of the volunteer work. The first team has the right to take legal action against the second team in case of a breach of this clause.',
        ];

        $AR_SPECIAL = [
$arBullet([
                'في حال اضطرار الفريق الثاني للغياب فعليه أخذ',
                'موافقة الفريق الأول بذلك.'
            ]),
            $arBullet([
                'يُخصم يوم عمل تطوعي للفريق الثاني من مكافأة',
                'التطوّع المخصصة له في حال تكرار التأخير عن مواعيد',
                'الدوام المحددة لأكثر من 3 مرات في الشهر مبلغاً يعادل',
                'قيمة يوم دوام / جلسة.'
            ]),
            $arBullet([
                'يتعهد الفريق الثاني بالحفاظ على سرّية جميع',
                'المعلومات والوثائق التي يطّلع عليها، وعدم استخدامها',
                'خارج نطاق العمل التطوّعي. ويحق للفريق الأول',
                'مقاضاة الفريق الثاني في حال إخلاله بهذا البند.'
            ])
        ];
    @endphp

    {{-- <x-contract.dual class="mt-1" :en="$asList($EN_SPECIAL)" :ar="$asList($AR_SPECIAL)" /> --}}
        @foreach ($EN_SPECIAL as $index => $enItem)
        <x-contract.dual class="mt-1" :en="$enItem" :ar="$AR_SPECIAL[$index]" />
    @endforeach
</div>