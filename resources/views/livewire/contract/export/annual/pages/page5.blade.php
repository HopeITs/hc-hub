@php
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';
    $p = fn (string $t) => '<div>'.$t.'</div>';

    $loc = $locale ?? app()->getLocale();
    $emp = $contract->employee;

    $nameEn = $emp?->getTranslation('name', 'en') ?? $emp?->getTranslation('name', $loc) ?? $emp?->name ?? '—';
    $nameAr = $Glyphs($emp?->getTranslation('name', 'ar') ?? $emp?->getTranslation('name', $loc) ?? $emp?->name ?? '—');

    $esc   = fn ($v) => e($v === '' || $v === null ? '—' : $v);
    $escAr = fn ($v) => e($v === '' || $v === null ? '—' : $Glyphs($v));
@endphp

<div class="avoid-break">
    <x-contract.dual rowClass="mt-2"
                     :en="$L('Important note:')"
                     :ar="$L($Glyphs(__('general.text33')))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="$p('Hope Center Aleppo / Head office is not obligated to any compensation upon the expiry or termination of the contract.')"
                     :ar="$Glyphs(__('general.text34'))"
    />
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-6"
                     :en="$p('I hereby certify having read and understood the conditions stipulated above and all attached documents and confirm that I accept them all.')"
                     :ar="$p($Glyphs('أقرّ بأنني قرأت وفهمت شروط العمل المنصوص عليها <br> وكافة المستندات المرفقة وأوافق عليها كلها.'))"
    />
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-6"
                     :en="$p('This contract was issued on 4 pages, printed on a single copy and kept at the Head Office in Aleppo. The contract is produced in Arabic and translated into English.')"
                     :ar="$p($Glyphs('صدر هذا العقد على 4 صفحات، مطبوعاً على نسخة واحدة محفوظة لدى الإدارة العامة في حلب. صدر هذا العقد باللغة العربية وترجم إلى اللغة الإنجليزية.'))"
    />
</div>

<div class="avoid-break mt-10">
    <div style="display:flex; justify-content:space-between; width:100%;">

        <!-- Second Party (left column) -->
        <div style="flex:1; text-align:left; padding-left:20px;">
            <div style="font-weight:bold; color:#b00;">
                Second Party<br>{{ $Glyphs('الفريق الثاني') }}
            </div>
            <div>
                Name and Signature<br>{{ $Glyphs('الاسم والتوقيع') }}
            </div>
            <div>
                {{ $esc($nameEn) }}<br>{{ $escAr($nameAr) }}
            </div>
        </div>

        <!-- First Party (right column) -->
        <div style="flex:1; text-align:right; padding-right:20px;">
            <div style="font-weight:bold; color:#b00;">
                First Party<br>{{ $Glyphs('الفريق الأول') }}
            </div>
            <div>
                Name and Signature<br>{{ $Glyphs('الاسم والتوقيع') }}
            </div>
            <div>
                Fredy Youssef<br>{{ $Glyphs('فريدي يوسف') }}
            </div>
        </div>

    </div>
</div>

<div class="avoid-break mt-8" style="text-align:center;">
    Date<br>{{ $Glyphs('التاريخ') }}
</div>


