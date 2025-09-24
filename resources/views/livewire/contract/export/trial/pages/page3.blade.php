@php
    use Illuminate\Support\Number;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;
    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $Digits = fn ($s) => $forPdf ? ar_digits((string)$s) : (string)$s;
    $L      = fn (string $t) => '<span class="h-label">'.$t.'</span>';
    $asList = fn (array $lines, $start=1) =>
        '<ol class="list-decimal space-y-3" start="'.$start.'" style="margin:0; padding-left:16px;">'
        .implode('', array_map(fn($l) => '<li>'.$l.'</li>', $lines))
        .'</ol>';

    $amount     = $contract->salary ?? null;
    $currencyId = (int) ($contract->currency ?? 0);

    $CURRENCY = [
        1 => ['code'=>'SYP','en'=>'Syrian Pounds','ar'=>'ليرة سورية'],
        2 => ['code'=>'USD','en'=>'US Dollars','ar'=>'دولار أمريكي'],
    ];
    $cur = $CURRENCY[$currencyId] ?? $CURRENCY[1];

    $amtEn = is_numeric($amount) ? Number::format($amount, locale:'en') : '—';
    $amtAr = is_numeric($amount) ? $Digits(Number::format($amount, locale:'en')) : '—';

    $EN_LIST_3 = [
        "The first party commits to pay a monthly stipend of {$amtEn} {$cur['code']} / {$cur['en']} only.",
        'The volunteer must commit to, abide by, and sign the Code of Conduct.',
    ];
    $AR_LIST_3 = [
        $Glyphs("يلتزم الفريق الأول بدفع مكافأة شهرية مقدارها {$amtAr} {$cur['code']} / {$cur['ar']} فقط لا غير."),
        $Glyphs('على الفريق الثاني الالتزام والتوقيع على قواعد السلوك الأخلاقي.'),
    ];

    $EN_LIST_4 = [
        'One day stipend will be deducted for repeated delays beyond 3 times per month.',
        'No leave is permitted during the trial period.',
        'Absence for valid reasons must not exceed 3 consecutive days, except in cases of death or severe sickness.',
        'The first party may terminate this contract before its expiry if the volunteer breaches the contract or Code of Conduct. In that case, due amounts will be paid up to termination date.',
    ];
    $AR_LIST_4 = [
        $Glyphs('يُحسم من مكافأة الفريق الثاني مقدار يوم عمل في حال تكرار التأخر عن الدوام أكثر من 3 مرات في الشهر.'),
        $Glyphs('لا يحق للمتطوع إجازات خلال فترة العقد التجريبي.'),
        $Glyphs('في حال الغياب لعذر قوي يجب إعلام الفريق الأول، على أن لا يتجاوز 3 أيام متتالية، باستثناء الوفاة أو المرض الشديد.'),
        $Glyphs('يحق للفريق الأول إنهاء العقد قبل انتهائه في حال خالف الفريق الثاني شروط العقد أو قواعد السلوك، وتُدفع له جميع مستحقاته حتى تاريخ الإنهاء.'),
    ];
@endphp

<div class="avoid-break">
    <x-contract.dual rowClass="mt-1" :en="$L('Third term:')" :ar="$L($Glyphs('البند الثالث:'))"/>
    <x-contract.dual rowClass="mt-2" :en="$asList($EN_LIST_3,6)" :ar="$asList($AR_LIST_3,6)"/>
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-5" :en="$L('Fourth term:')" :ar="$L($Glyphs('البند الرابع:'))"/>
    <x-contract.dual rowClass="mt-2" :en="$asList($EN_LIST_4)" :ar="$asList($AR_LIST_4)"/>
</div>
