@php
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;
    $G = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;

    $loc = $locale ?? app()->getLocale();
    $emp = $contract->employee;

    $nameEn  = $emp?->name_en ?? '—';

    $nameAr = $G(
   $emp?->name_ar ?? '—'
    );

    $esc   = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));
    $escAr = fn ($v) => e((string)($v === '' || $v === null ? '—' : $G($v)));

    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';
    $p = fn (string $t) => '<div>'.$t.'</div>';
@endphp

<div class="avoid-break">
    <x-contract.dual rowClass="mt-2"
                     :en="$L('Important note:').$p('Hope Center Aleppo / Head office is not obligated to any compensation upon the expiry or termination of the contract.')"
                     :ar="$L($G('ملاحظة هامة:')).$p($G('لا يلتزم الفريق الأول بأي تعويضات عند انتهاء عقد المتطوع أو إنهائه.'))"
    />
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-6"
                     :en="$p('I hereby certify having read and understood the conditions stipulated above and all attached documents and confirm that I accept them all.')"
                     :ar="$p($G('أقرّ بأنني قرأت وفهمت شروط العمل المنصوص عليها وكافة المستندات المرفقة وأوافق عليها كلها.'))"
    />
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-6"
                     :en="$p('This contract was issued on 4 pages, printed on a single copy and kept at the Head Office in Aleppo. The contract is produced in Arabic and translated into English.')"
                     :ar="$p($G('صدر هذا العقد على 4 صفحات، مطبوعاً على نسخة واحدة محفوظة لدى الإدارة العامة في حلب. صدر هذا العقد باللغة العربية وترجم إلى اللغة الإنجليزية.'))"
    />
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-10"
                     :en="$L('first party').$p('name and signature').$p('Fredy Youssef')"
                     :ar="$L($G('الفريق الأول')).$p($G('الاسم والتوقيع')).$p($G('فريدي يوسف'))"
                     enClass="text-left" arClass="text-right"
    />
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-10"
                     :en="$L('second party').$p('name and signature').$p($esc($nameEn))"
                     :ar="$L($G('الفريق الثاني')).$p($G('الاسم والتوقيع')).$p($esc($nameAr))"
                     enClass="text-left" arClass="text-right"
    />
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-8" :en="'date'" :ar="$G('التاريخ')"/>
</div>
