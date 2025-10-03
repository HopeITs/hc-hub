@php
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';
    $p = fn (string $t) => '<div>'.$t.'</div>';

    $loc = $locale ?? app()->getLocale();
    $emp = $contract->employee;

    $nameEn = $emp?->getTranslation('name', 'en')
        ?? $emp?->getTranslation('name', $loc)
        ?? $emp?->name ?? '—';

    $nameAr = $Glyphs(
        $emp?->getTranslation('name', 'ar')
        ?? $emp?->getTranslation('name', $loc)
        ?? $emp?->name ?? '—'
    );

    $esc   = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));
    $escAr = fn ($v) => e((string)($v === '' || $v === null ? '—' : $Glyphs($v)));
@endphp

<div class="avoid-break">
    <x-contract.dual rowClass="mt-2"
                     :en="$L('Important note:')"
                     :ar="$L($Glyphs('ملاحظة هامة:'))"/>
    <x-contract.dual rowClass="mt-2"
                     :en="$p('Hope Center Aleppo / Head office is not obligated to any compensation upon the expiry or termination of the contract.')"
                     :ar="$p($Glyphs('لا يلتزم الفريق الأول بأي تعويضات عند انتهاء عقد المتطوع أو إنهائه.'))"/>
</div>

<div class="avoid-break mt-6">
    <x-contract.dual
        :en="$p('I hereby certify having read and understood the conditions stipulated above and all attached documents and confirm that I accept them all.')"
        :ar="$p($Glyphs('أقرّ بأنني قرأت وفهمت شروط العمل المنصوص عليها وكافة المستندات المرفقة وأوافق عليها كلها.'))"/>
</div>

<div class="avoid-break mt-6">
    <x-contract.dual
        :en="$p('This contract was issued on 4 pages, printed on a single copy and kept at the Head Office in Aleppo. The contract is produced in Arabic and translated into English.')"
        :ar="$p($Glyphs('صدر هذا العقد على 4 صفحات، مطبوعاً على نسخة واحدة محفوظة لدى الإدارة العامة في حلب. صدر هذا العقد باللغة العربية وترجم إلى اللغة الإنجليزية.'))"/>
</div>

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
<div class="avoid-break" style="text-align:right; position: absolute; bottom:35%; right:160px;">
    <div style="margin-bottom:6px;">
        Date/{{ $Glyphs('التاريخ') }}
    </div>
</div>
