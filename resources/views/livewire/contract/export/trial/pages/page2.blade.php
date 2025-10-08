@php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;
    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $Digits = fn ($s) => $forPdf ? ar_digits((string)$s) : (string)$s;
    $esc    = fn ($v) => e($v === '' || $v === null ? '—' : $v);
    $escAr  = fn ($v) => e($v === '' || $v === null ? '—' : $Glyphs($v));
    $L      = fn (string $t) => '<span class="h-label">'.$t.'</span>';
    $asList = fn (array $lines) =>
        '<ol class="list-decimal space-y-3" style="margin:0; padding-left:16px;">'
        .implode('', array_map(fn($l) => '<li>'.$l.'</li>', $lines))
        .'</ol>';

    // Dates
    $start = $contract->start_date ? Carbon::parse($contract->start_date) : null;
    $end   = $contract->end_date   ? Carbon::parse($contract->end_date)   : null;
    $startStr   = $start ? $start->format('d/m/Y') : '—';
    $endStr     = $end   ? $end->format('d/m/Y')   : '—';
    $startStrAr = $Digits($startStr);
    $endStrAr   = $Digits($endStr);

    // Duration
    $months = ($start && $end) ? max(1, $start->diffInMonths($end)) : 3;
    $durEn = match($months){
        1=>'One Month',2=>'Two Months',3=>'Three Months',4=>'Four Months',5=>'Five Months',6=>'Six Months',default=>$months.' Months'
    };
    $durAr = match($months){
        1=>'شهر واحد',2=>'شهران',3=>'ثلاثة أشهر',4=>'أربعة أشهر',5=>'خمسة أشهر',6=>'ستة أشهر',default=>$Digits($months).' أشهر'
    };

    $placeEn = 'Aleppo – Christian Hope Center – Head Office';
    $placeAr = $Glyphs('مكان العمل: حلب – مركز الرجاء المسيحي – المكتب الرئيسي');

    $EN_ITEMS = [
        "The contract period is {$durEn} starting from {$esc($startStr)} until {$esc($endStr)} with a possibility to be extended by agreement, provided it does not exceed six months.",
        'The trial period ends by confirming a permanent contract with the volunteer or dismissing his/her services according to performance evaluation.',
        "The volunteer is committed to serving as MEAL Officer during standard hours (Mon–Fri 09:30–17:30).<br><br><em>Place of Work:</em> {$esc($placeEn)}.",
        'Saturdays may be working days when necessary and are not treated as extra hours.',
        'The volunteer must return all equipment, materials, and documents immediately upon termination according to the updated receipt list.',
    ];

    $AR_ITEMS = [
        "مدة العقد {$durAr} ابتداءً من {$escAr($startStrAr)} وحتى {$escAr($endStrAr)}، مع إمكانية التمديد باتفاق الطرفين ووفق تقييم الأداء على أن لا تتجاوز ستة أشهر.",
        $Glyphs('تنتهي الفترة التجريبية بتثبيت المتطوع بعقد دائم أو الاعتذار عن الخدمات وفق تقييم أداء واضح.'),
        $Glyphs('يلتزم الفريق الثاني بتقديم خدماته كمسؤول MEAL وفق الدوام الإداري من الاثنين إلى الجمعة 09:30–17:30.').'<br><br>'.$placeAr,
        $Glyphs('يُعتبر السبت يوماً للعمل عند الحاجة ولا يُحتسب كدوام إضافي.'),
        $Glyphs('يلتزم الفريق الثاني عند انتهاء الفترة بتسليم جميع العهد والمواد والوثائق التي استلمها فوراً إلى المركز وفق لائحة الاستلام المحدثة.'),
    ];
@endphp

<div class="avoid-break">
    <x-contract.dual rowClass="mt-1" :en="$L('Third term:')" :ar="$L($Glyphs('البند الثالث:'))"/>
    <x-contract.dual rowClass="mt-2" :en="$asList($EN_ITEMS)" :ar="$asList($AR_ITEMS)"/>
</div>
