@php
    use App\Support\ArabicText as AR;

    $forPdf  = $forPdf ?? false;
    $Glyphs  = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $Digits  = fn ($s) => $forPdf ? ar_digits((string)$s) : (string)$s;
    $L       = fn (string $t) => '<span class="h-label">'.$t.'</span>';

    // Employee name
    $empNameEn =  $contract->employee?->name_en ?? '—';
    $empNameAr =  $Glyphs($contract->employee?->name_ar ?? '—');

    $esc   = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));
    $escAr = fn ($v) => e((string)($v === '' || $v === null ? '—' : $Glyphs($v)));
@endphp

{{-- Fourth term (3) --}}
<x-contract.dual rowClass="mt-6 mb-6"
                 :en="'3. The first party has the right to terminate this contract at any time before its expiry date in case the second party violates the terms of this contract and the volunteering code of conduct; in this case, all due amounts must be paid to the second party up to the termination date.'"
                 :ar="'3. يحق للفريق الأول إنهاء هذا العقد في أي وقت قبل انتهاء مدته  في حال قيام الفريق الثاني بمخالفة بنود هذا العقد والقواعد الأخلاقية للتطوع؛ وعليه في هذه الحالة أن يدفع  للفريق الثاني كافة المبالغ المستحقة له حتى تاريخ الإنهاء.'"
/>

{{-- Fifth term --}}
<div class="avoid-break mt-4">
    <x-contract.dual :en="$L('Fifth term:')" :ar="$L($Glyphs('البند الخامس:'))"/>
</div>

<x-contract.dual rowClass="mt-6 mb-6"
                 :en="'I hereby confirm that I have read and understood the work conditions and all the attached documents and I agree to all of the stated.'"
                 :ar="$Glyphs('أفيد بأنني قرأت وفهمت شروط العمل المنصوص عليها وكافة المستندات المرفقة وأوافق على كل ما ورد.')"
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
        {{ $esc($empNameEn) }}/{{ $escAr($empNameAr) }}
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
<div class="avoid-break" style="text-align:right; position: absolute; bottom:19%; right:160px;">
    <div style="margin-bottom:6px;">
        Date/{{ $Glyphs('التاريخ') }}
    </div>
</div>
