@php
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;
    $Glyphs = fn($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $L = fn($t) => '<span class="h-label">'.$t.'</span>';

    $esc   = fn ($v) => e($v === '' || $v === null ? '—' : $v);
    $escAr = fn ($v) => e($v === '' || $v === null ? '—' : $Glyphs($v));

    $emp = $contract->employee;
    $nameEn =  $emp?->name_en ?? '—';
    $nameAr =  $Glyphs($emp?->name_ar ??  '—');

    // define helper from page 3
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

    $EN_LIST_4_CONT = [
        'The first party has the right to terminate this contract at any time before its expiry date in case the second party violates the terms of this contract and the volunteering code of conduct; in this case, all due amounts must be paid to the second party up to the termination date.',
    ];

    $AR_LIST_4_CONT = [
        $Glyphs('يحق للفريق الأول إنهاء هذا العقد في أي وقت قبل <br> انتهاء مدته في حال قيام الفريق الثاني <br> بمخالفة بنود هذا العقد والقواعد الأخلاقية للتطوع، وعليه في هذه الحالة أن يدفع للفريق الثاني كافة المبالغ المستحقة له حتى تاريخ الانهاء.'),
    ];

    $EN_FIFTH_TERM_LABEL = $L('Fifth term:');
    $AR_FIFTH_TERM_LABEL = $L($Glyphs('البند الخامس:'));
    $EN_FIFTH_TEXT = 'I hereby confirm that I have read and understood the work conditions and all the attached documents and I agree to all of the stated.';
    $AR_FIFTH_TEXT = $Glyphs('أفيد بأنني قرأت وفهمت شروط العمل المنصوص عليها <br> وكافة المستندات المرفقة وأقر بموافقتي على كل <br> ما ورد.');

    $EN_NOTE_1 = 'This contract was issued on three pages, printed as a single copy, and has only one version secured in the head office in Aleppo.';
    $AR_NOTE_1 = $Glyphs('صدر هذا العقد على ثلاث صفحات، مطبوعاً على نسخة واحدة محفوظة لدى الإدارة العامة في حلب.');
    $EN_NOTE_2 = 'This contract was issued in Arabic and translated into English.';
    $AR_NOTE_2 = $Glyphs('صدر هذا العقد بالنسخة العربية وترجم إلى اللغة <br> الإنكليزية.');

@endphp

<x-contract.dual
    rowClass="mt-6"
    :en="$asList($EN_LIST_4_CONT, 'en', 3)"
    :ar="$asList($AR_LIST_4_CONT, 'ar', 3)"
/>

{{-- Fifth Term --}}
<x-contract.dual rowClass="mt-4" :en="$EN_FIFTH_TERM_LABEL" :ar="$AR_FIFTH_TERM_LABEL"/>
<x-contract.dual :en="$EN_FIFTH_TEXT" :ar="$AR_FIFTH_TEXT"/>
<x-contract.dual rowClass="mt-2" :en="$EN_NOTE_1" :ar="$AR_NOTE_1"/>
<x-contract.dual :en="$EN_NOTE_2" :ar="$AR_NOTE_2"/>

<div style="position: absolute; left: 0; text-align: center; width: 50%;margin-top:60px;">
    <div style="font-weight:bold; color:#b00; margin-bottom:12px;">
        Second Party/{{ $Glyphs('الفريق الثاني') }}
    </div>
    <div style="margin-bottom:6px;">
        Name and Signature/{{ $Glyphs('الاسم والتوقيع') }}
    </div>
    <div>
        {{ $esc($nameEn) }}/{{ $esc($nameAr) }}
    </div>
</div>

<!-- First Party -->
<div style="position: absolute; right: 0; text-align: center;width: 50%;margin-top: 60px;">
    <div style="font-weight:bold; color:#b00; margin-bottom:12px;">
        First Party/{{ $Glyphs('الفريق الأول') }}
    </div>
    <div style="margin-bottom:6px;">
        Name and Signature/{{ $Glyphs('الاسم والتوقيع') }}
    </div>
    <div>
        Fredy Youssef/{{ $Glyphs('فريدي يوسف') }}
    </div>
</div>

<!-- Date centered -->
<div class="avoid-break" style="text-align:right; position: absolute; bottom:18%; right:160px">
    <div style="margin-bottom:6px;">
        Date/{{ $Glyphs('التاريخ') }}
    </div>
</div>
