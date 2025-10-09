@php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;
    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $L      = fn (string $t) => '<span class="h-label">'.$t.'</span>';

    $start = $contract->start_date ? Carbon::parse($contract->start_date) : null;
    $end   = $contract->end_date   ? Carbon::parse($contract->end_date)   : null;

    $startEn = $start ? $start->format('d/m/Y') : '—';
    $endEn   = $end   ? $end->format('d/m/Y')   : '—';
    $startAr = $start ? ar_digits($start->format('d/m/Y')) : '—';
    $endAr   = $end   ? ar_digits($end->format('d/m/Y'))   : '—';

    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';

    // === Numbered list builder ===
    $asList = fn(array $lines, string $lang, int $start = 1) =>
        '<ul style="margin:0;padding:0;list-style:none;">' .
        implode('', array_map(function ($l, $i) use ($lang, $start) {
            $num = $start + $i;
            return $lang === 'ar'
                ? '<li style="direction:rtl;text-align:right;position:relative;padding-right:18px;padding-bottom:20px;">'
                    . '<span style="position:absolute;right:0;">.'.$num.'</span>'.$l.'</li>'
                : '<li style="position:relative;padding-left:16px;padding-bottom:14px;">'
                    . '<span style="position:absolute;left:0;">'.$num.'.</span>'.$l.'</li>';
        }, $lines, array_keys($lines))) .
        '</ul>';

    $EN_LIST_3 = [
        'The contract is considered valid <br> from  '.$startEn.' <Br> until '.$endEn.',<br>  and the first two months are considered a trial period for the second party where the first party is entitled to terminate the contract and not continue with the second party.<br><br>'
    ];

    $AR_LIST_3 = [
        $Glyphs(__('general.text45')).'<br>'.$startAr.' '.$Glyphs(__('general.text46')).'<br>'.$endAr.' '.$Glyphs(__('general.text47')).'<br>'.$Glyphs(__('general.text48')),
    ];
@endphp

<div class="avoid-break mt-8" style="line-height:0.9">
    <x-contract.dual :en="$L('First term:')" :ar="$L($Glyphs('البند الأول:'))"/>
    <x-contract.dual :en="'The introduction of this contract is considered an indissoluble part of it.'"
                     :ar="$Glyphs(__('general.text37'))"/>
</div>

<div class="avoid-break mt-8" style="line-height:0.9">
    <x-contract.dual :en="$L('Second term:')" :ar="$L($Glyphs('البند الثاني:'))"/>
    <x-contract.dual :en="'The second party is committed to implementing all volunteering activities agreed on with the first party and all requested programming services.'"
                     :ar="$Glyphs(__('general.text38'))"/>
</div>

<div class="avoid-break mt-8">
    <x-contract.dual :en="$L('Third term:')" :ar="$L($Glyphs('البند الثالث:'))"/>
</div>

<x-contract.dual rowClass="mt-6"
                 :en="$asList($EN_LIST_3, 'en', 1)"
                 :ar="$asList($AR_LIST_3, 'ar', 1)"/>
<div class="avoid-break mt-4">
    <x-contract.dual
        :en="'This contract will not be renewed without a written agreement of both parties, whereas each party shall inform the other of the contract termination one month prior to the end of the contract.'"
        :ar="        $Glyphs('لا يتجدد هذا العقد لمدة أخرى إلا بموافقة الفريقين <br>  خطياً، على أن يعلم كل فريق الآخر بعدم  <Br> التمديد قبل شهر من انتهاء العقد.')
"/>
</div>

<div class="avoid-break mt-8">
    <x-contract.dual
        :en="'Place of Work: Aleppo – Christian Hope Center – Head Office'"
        :ar="        $Glyphs('مكان العمل: حلب - مركز الرجاء المسيحي - المكتب الرئيسي')
"/>
</div>
