{{-- @php
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $Digits = fn ($s) => $forPdf ? ar_digits((string)$s) : (string)$s;
    $L      = fn (string $t) => '<span class="h-label">'.$t.'</span>';

    $emp = $contract->employee;
    $nameEn = $emp?->name_en ?? '—';
    $nameAr = $emp?->name_ar ?? '—';

    $startIndex = 5;

    $EN_LIST_5 = [
        'If the second party wishes to terminate the contract, the second party is obliged to inform the first party at least two weeks before the end of the contract period.',
    ];
    $AR_LIST_5 = [
        $Glyphs('في حال رغبة الفريق الثاني بفسخ العقد يلتزم بأن  <Br> يُعلم الفريق الأول قبل أسبوعين على الأقل  <br> من انتهاء العقد.'),
    ];

    $asList = fn (array $lines, array $indices, string $lang = 'en') =>
        '<ul style="margin:0; padding-left:0; padding-right:0; list-style:none;">'
        . implode('', array_map(function($l, $i) use ($lang, $indices) {
            if ($lang === 'ar') {
                return '<li style="direction:rtl; text-align:right; position:relative; padding-right:18px; padding-bottom:40px;">'
                     . '<span style="position:absolute; right:0;">.'.$indices[$i].'</span>'
                     . $l
                     . '</li>';
            }
            return '<li style="position:relative; padding-left:18px; padding-bottom:20px;">'
                 . '<span style="position:absolute; left:0;">'.$indices[$i].'.</span>'
                 . $l
                 .'</li>';
        }, $lines, array_keys($lines)))
        . '</ul>';

    $indices = range($startIndex, $startIndex + count($EN_LIST_5) - 1);
    $esc   = fn ($v) => e($v === '' || $v === null ? '—' : $Glyphs($v));
    $escAr = fn ($v) => e($v === '' || $v === null ? '—' : $Glyphs($v));
@endphp


<x-contract.dual rowClass="mt-6"
                 :en="$asList($EN_LIST_5, $indices, 'en')"
                 :ar="$asList($AR_LIST_5, $indices, 'ar')"
/>
<div class="avoid-break mt-4">
    <x-contract.dual :en="$L('Sixth term:')" :ar="$L($Glyphs('البند السادس:'))"/>
</div>

<div class="avoid-break mt-4">
    <x-contract.dual :en="'I hereby confirm that I have read and understood the work conditions and all the attached documents and I agree to all of the stated.'"
                     :ar="$Glyphs('أفيد بأنني قرأت وفهمت شروط العمل المنصوص  عليها <Br> وكافة المستندات المرفقة وأقر بموافقتي على  <br> كل ما ورد.')"/>
</div>

<div class="avoid-break mt-4">
    <x-contract.dual :en="'This contract was issued on three pages, printed as a single copy, and has only one version secured in the head office in Aleppo.'"
                     :ar="$Glyphs('صدر هذا العقد على ثلاث صفحات، مطبوعاً على نسخة واحدة محفوظة لدى الإدارة العامة في حلب.')"/>

    <x-contract.dual :en="'This contract was issued in Arabic and translated into English.'"
                     :ar="$Glyphs('صدر هذا العقد بالنسخة العربية وترجم إلى اللغة  <Br> الإنكليزية.')"/>
</div>

<div style="position: absolute; left: 0; text-align: center; width: 50%;margin-top: 35px;">
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
<div style="position: absolute; right: 0; text-align: center;width: 50%;margin-top: 35px;">
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
<div class="avoid-break" style="text-align:right; position: absolute; bottom:25%; right:160px">
    <div style="margin-bottom:6px;">
        Date/{{ $Glyphs('التاريخ') }}
    </div>
</div> --}}


@php
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $SmartGlyphs = fn ($s, $limit = 2000) => $forPdf ? AR::smartGlyphs((string)$s, $limit) : (string)$s;
    $LB     = fn ($t) => '<span class="h-label-bold">' . $t . '</span>';

    $emp    = $contract->employee;

    $nameEn = trim(($emp?->first_name_en ?? '') . ' ' . ($emp?->father_name_en ?? '') . ' ' . ($emp?->last_name_en ?? ''));
    $nameEn = $nameEn !== '' ? $nameEn : '—';

    $nameAr = trim(($emp?->first_name_ar ?? '') . ' ' . ($emp?->father_name_ar ?? '') . ' ' . ($emp?->last_name_ar ?? ''));
    $nameAr = $nameAr !== '' ? $nameAr : '—';

    $esc   = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));
    $escAr = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));

    $ar_term_13 = 
        '<strong>' . $Glyphs('لا يخضع لقانون العمل') . '</strong> ' . $Glyphs('هو عقد تطوّعي') . ' ' . $LB($Glyphs('إن هذا العقد') . ' .13') . '<br>' .
        $Glyphs('أو أي تعديل للقانون أو أي قانون') . ' <strong>' . $Glyphs('رقم /17/ لعام 2010') . '</strong><br>' .
        '<strong>92 ' . $Glyphs('لا يخضع لقانون التأمينات الاجتماعية رقم') . '</strong> ' . $Glyphs('جديد، وكذلك') . '<br>' .
        $Glyphs('ولا يحق للفريق الثاني المطالبة') . ' <strong>' . $Glyphs('لعام 1959 وتعديلاته،') . '</strong><br>' .
        $Glyphs('بأي تعويض نهاية خدمة عند انتهاء عقد التطوّع أو إنهائه.');
@endphp


    <div class="avoid-break mt-4">
        <x-contract.dual 
            :en="$LB('13. This contract') . ' is a voluntary contract and is <strong>not subject to Labor Law No. /17/ of 2010</strong> or any amendments to the law or any new law, and it is also <strong>not subject to social security Law No. 92 of 1959 and its amendments</strong>. The second team has no right to claim any end-of-service compensation upon the expiration or termination of the second party contract.'"
            :ar="$ar_term_13" />
    </div>

    <div class="avoid-break mt-4">
        <x-contract.dual 
            :en="$LB('14. The contract is issued') . ' in Arabic and translated into English.'"
            :ar="$SmartGlyphs('باللغة العربية وتُرجم إلى اللغة الإنكليزية.') . ' ' . $LB($Glyphs('صدر هذا العقد') . ' .14')" />
    </div>

    <div class="avoid-break mt-4">
        <x-contract.dual 
            :en="$LB('15. This contract was issued') . ' on 4 pages and contains 15 clauses, printed in a single copy preserved at the Head Office in Aleppo.'"
            :ar="
                $SmartGlyphs('على 4 صفحات وعلى خمسة عشر بنداً،') . ' ' . $LB($Glyphs('صدر هذا العقد') . ' .15') . '<br>' .
                $SmartGlyphs('مطبوعاً على نسخة واحدة محفوظة لدى الإدارة') . '<br>' .
                $SmartGlyphs('العامة في حلب.')
            " />
    </div>

    <div class="avoid-break mt-6">
        <x-contract.dual 
            :en="'I hereby certify having read and understood the conditions stipulated above and all the attached documents and confirm that I accept them all.'"
            :ar="
                $SmartGlyphs('أفيد بأنني قرأت وفهمت شروط العمل المنصوص عليها أعلاه') . '<br>' .
                $SmartGlyphs('وكافة المستندات المرفقة وأقر بموافقتي على كل ما ورد،') . '<br>' .
                $SmartGlyphs('وعليه أوقّع.')
            " />
    </div>

    <div class="avoid-break" style="position: relative; margin-top: 45px; min-height: 220px;">
        
        <div style="position: absolute; left: 0; text-align: center; width: 48%;">
            <div style="margin-bottom: 12px; font-size: 12pt;">
                {!! $LB('Second Party') !!} / {!! $LB($SmartGlyphs('الفريق الثاني')) !!}
            </div>
            <div style="margin-bottom: 15px; font-size: 10pt;">
                Name and Signature / {{ $SmartGlyphs('الاسم والتوقيع') }}
            </div>
            <div style="line-height: 1.6; margin-bottom: 25px; font-size: 11pt;">
                <strong>{{ $SmartGlyphs($escAr($nameAr)) }}</strong>
                <br>
                <strong>{{ $esc($nameEn) }}</strong>
            </div>
            <div style="font-size: 10pt;">
                Date / {{ $SmartGlyphs('التاريخ') }}
            </div>
        </div>

        <div style="position: absolute; right: 0; text-align: center; width: 48%;">
            <div style="margin-bottom: 12px; font-size: 12pt;">
                {!! $LB('First Party') !!} / {!! $LB($SmartGlyphs('الفريق الأول')) !!}
            </div>
            <div style="margin-bottom: 15px; font-size: 10pt;">
                Name and Signature / {{ $SmartGlyphs('الاسم والتوقيع') }}
            </div>
            <div style="line-height: 1.6; margin-bottom: 25px; font-size: 11pt;">
                <strong>{{ $SmartGlyphs('فريدي جرجس يوسف') }}</strong>
                <br>
                <strong>Fredy Georges Youssef</strong>
            </div>
            <div style="font-size: 10pt;">
                Date / {{ $SmartGlyphs('التاريخ') }}
            </div>
        </div>

    </div>
