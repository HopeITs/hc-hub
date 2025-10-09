@php
    use App\Support\ArabicText as AR;

    $forPdf  = $forPdf ?? false;
    $Glyphs  = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $Digits  = fn ($s) => $forPdf ? ar_digits((string)$s) : (string)$s;
    $L       = fn (string $t) => '<span class="h-label">'.$t.'</span>';

    $emp = $contract->employee;

    $nameEn = $emp?->name_en ?? '—';
    $nameAr = $emp?->name_ar ?? '—';

    $esc   = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));
    $escAr = fn ($v) => e((string)($v === '' || $v === null ? '—' : $Glyphs($v)));
@endphp

{{-- 5. Clause --}}
<x-contract.dual rowClass="mt-6 mb-10"
                 :en="'5. If the second party wishes to terminate the contract, the second party is obliged to inform the first party at least two weeks before the end of the contract period.'"
                 :ar="'5. في حال رغبة الفريق الثاني بفسخ العقد يلتزم بأن يعلم الفريق الأول قبل أسبوعين على الأقل من انتهاء العقد.'"
/>

{{-- Fifth term --}}
<div class="avoid-break mt-4">
    <x-contract.dual :en="$L('Fifth term:')" :ar="$L($Glyphs('البند الخامس:'))"/>
</div>

<x-contract.dual rowClass="mt-6 mb-6"
                 :en="'I hereby confirm that I have read and understood the work conditions and all the attached documents and I agree to all of the stated.'"
                 :ar="$Glyphs('أفيد بأنني قرأت وفهمت شروط العمل المنصوص عليها وكافة المستندات المرفقة وأقر بموافقتي على كل ما ورد.')"
/>

<x-contract.dual rowClass="mt-2 mb-2"
                 :en="'This contract was issued on three pages, printed as a single copy, and has only one version secured in the head office in Aleppo.'"
                 :ar="$Glyphs('صدر هذا العقد على ثلاث صفحات، مطبوعاً على نسخة واحدة محفوظة لدى الإدارة العامة في حلب.')"
/>

<x-contract.dual rowClass="mt-2 mb-10"
                 :en="'This contract was issued in Arabic and translated into English.'"
                 :ar="$Glyphs('صدر هذا العقد بالنسخة العربية وترجم إلى اللغة الإنكليزية.')"
/>

<!-- Second Party -->
<div style="position: absolute; left: 0; text-align: center; width: 50%; margin-top: 35px;">
    <div style="font-weight:bold; color:#b00; margin-bottom:12px;">
        Second Party/{{ $Glyphs('الفريق الثاني') }}
    </div>
    <div style="margin-bottom:6px;">
        Name and Signature/{{ $Glyphs('الاسم والتوقيع') }}
    </div>
    <div>
        {{ $esc($nameEn) }}/{{ $escAr($nameAr) }}
    </div>
</div>

<!-- First Party -->
<div style="position: absolute; right: 0; text-align: center; width: 50%; margin-top: 35px;">
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

<!-- Date -->
<div class="avoid-break" style="text-align:right; position: absolute; bottom:24%; right:160px;">
    <div style="margin-bottom:6px;">
        Date/{{ $Glyphs('التاريخ') }}
    </div>
</div>
