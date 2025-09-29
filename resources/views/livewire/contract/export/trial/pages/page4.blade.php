@php
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;
    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $escAr  = fn ($v) => e($v === '' || $v === null ? '—' : $Glyphs($v));
    $L      = fn (string $t) => '<span class="h-label">'.$t.'</span>';
    $p      = fn (string $t) => '<div>'.$t.'</div>';

    $emp = $contract->employee;
    $nameEn = $emp?->getTranslation('name','en') ?? $emp?->name ?? '—';
    $nameAr = $Glyphs($emp?->getTranslation('name','ar') ?? $emp?->name ?? '—');
@endphp

<div class="avoid-break">
    <x-contract.dual rowClass="mt-1" :en="$L('Fifth term:')" :ar="$L($Glyphs('البند الخامس:'))"/>
    <x-contract.dual :en="$p('I confirm I have read and understood the conditions and agree to all attached documents.')"
                     :ar="$p($Glyphs('أقر بأنني قرأت وفهمت شروط العمل المنصوص عليها وكافة المستندات المرفقة وأوافق عليها.'))"/>
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-2"
                     :en="$p('This contract was issued on three pages, printed as a single copy and kept at the head office in Aleppo.')"
                     :ar="$p($Glyphs('صدر هذا العقد على ثلاث صفحات، مطبوعاً على نسخة واحدة محفوظة لدى الإدارة العامة في حلب.'))"/>
    <x-contract.dual :en="$p('This contract was issued in Arabic and translated into English.')"
                     :ar="$p($Glyphs('صدر هذا العقد بالعربية وترجم إلى الإنجليزية.'))"/>
</div>

<div class="avoid-break mt-6">
    <x-contract.dual rowClass="font-semibold" :en="$L('First Party')" :ar="$L($Glyphs('الفريق الأول'))"/>
    <x-contract.dual :en="$p('Name and Signature').$p('Fredy Youssef')"
                     :ar="$p($Glyphs('الاسم والتوقيع')).$p($Glyphs('فريدي يوسف'))" enClass="text-left" arClass="text-right"/>
</div>

<div class="avoid-break mt-6">
    <x-contract.dual rowClass="font-semibold" :en="$L('Second Party')" :ar="$L($Glyphs('الفريق الثاني'))"/>
    <x-contract.dual :en="$p('Name and Signature').$p(e($nameEn))"
                     :ar="$p($Glyphs('الاسم والتوقيع')).$p($escAr($nameAr))" enClass="text-left" arClass="text-right"/>
</div>

<div class="avoid-break mt-6">
    <x-contract.dual :en="$p('Date: __________________')" :ar="$p($Glyphs('التاريخ: __________________'))"/>
</div>
