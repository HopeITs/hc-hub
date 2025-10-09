@php
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
        $Glyphs('في حال اضطرار الفريق الثاني للغياب، فعليه أخذ موافقة الفريق الأول بذلك.'),
    ];
@endphp

{{-- Third Term --}}
<div class="avoid-break mt-8">
    <x-contract.dual :en="$L('Third term:')" :ar="$L($Glyphs('البند الثالث:'))"/>
</div>

<x-contract.dual
    rowClass="mt-6"
    :en="$asList($EN_LIST_3, 'en', 2)"
    :ar="$asList($AR_LIST_3, 'ar', 2)"
/>

{{-- Fourth Term --}}
<div class="avoid-break mt-4">
    <x-contract.dual :en="$L('Fourth term:')" :ar="$L($Glyphs('البند الرابع:'))"/>
</div>

<x-contract.dual
    rowClass="mt-6"
    :en="$asList($EN_LIST_4, 'en', 1)"
    :ar="$asList($AR_LIST_4, 'ar', 1)"
/>
