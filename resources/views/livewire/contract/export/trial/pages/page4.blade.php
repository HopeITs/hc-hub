@php
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
</div>
