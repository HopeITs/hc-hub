@php
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';
    $p = fn (string $t) => '<div>'.$t.'</div>';
    $asList = fn (array $lines) => '<ul style="margin:0; padding-left:14px;">'.implode('', array_map(fn($l) => '<li>'.$l.'</li>', $lines)).'</ul>';
@endphp

<div class="avoid-break mt-2" style="line-height: 0.8">
    <x-contract.dual rowClass="mt-4"
                     :en="$L('Supervision:').$p('Supervision is the responsibility of the Christian Hope Center Syria / Head Office Director. Depending on the circumstances, the Project Director may delegate supervision to another individual.')"
                     :ar="$L($Glyphs('الإشراف:')).$p($Glyphs('هو مسؤولية مدير مركز الرجاء المسيحي في سوريا، وحسب الظروف قد يفوّض مدير المشروع عملية الإشراف لشخص آخر.'))"
    />
</div>

<div class="avoid-break mt-4" style="line-height: 0.7">
    <x-contract.dual rowClass="mt-6"
                     :en="$L('Annual Leave:')"
                     :ar="$L($Glyphs('إجازات العمل التطوعي:'))"
    />
    <x-contract.dual rowClass="mt-6"
                     :en="$p('The volunteer is entitled to 12 days of working days leave during the validity period of the contract, with it being proportional to the duration of the contract.')"
                     :ar="$p($Glyphs(__('general.text27')))"
    />
    <x-contract.dual rowClass="mt-8"
                     :en="$p('The volunteer is entitled to 24 days of sickness leave in cases of operations and infectious diseases: 12 days paid and 12 days paid at half the value of the monthly stipend, provided that a medical report is submitted to justify the leave.')"
                     :ar="$p($Glyphs(__('general.text28')))"
    />
    <x-contract.dual rowClass="mt-6"
                     :en="$p('In case the volunteer consumes all annual and sickness leave days, he/she will be given twelve days of leave with the deduction of the number of leave days amount from monthly stipend.')"
                     :ar="$p($Glyphs(__('general.text29')))"
    />
</div>

<div class="avoid-break mt-6" style="line-height: 0.8">
    <x-contract.dual
        :en="$L('Internal Rules and Regulations:')"
        :ar="$L($Glyphs('القوانين والأنظمة الداخلية:'))"
    />
    <x-contract.dual
        :en="$p('Volunteer acknowledges and pledges to commit to the following attached documents:')"
        :ar="$p($Glyphs(__('general.text30')))"
    />
    @php
        $EN_LIST = ['Job description.', 'Code of Conduct.', 'Christian Hope Center Policies.'];
        $AR_LIST = [$Glyphs('توصيف العمل التطوعي.'), $Glyphs('القواعد الأخلاقية للتطوع.'), $Glyphs('سياسات مركز الرجاء المسيحي.')];
    @endphp
    <x-contract.dual rowClass="mt-2 "
                     :en="collect($EN_LIST)->map(fn($i) => '-    '.$i)->implode('<br>')"
                     :ar="collect($AR_LIST)->map(fn($i) => $i.$Glyphs('  -'))->implode('<br>')"
    />
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-6"
                     :en="$L('Expiration of the agreement:').$p('This agreement shall be terminated immediately in the event of:')"
                     :ar="$L($Glyphs('انتهاء العقد:')).$p($Glyphs('يعتبر هذا العقد منتهياً في الحالات التالية:'))"
    />
    @php
        $EN_END = ['Closure of the Christian Hope Center in Syria.'];
        $AR_END = [$Glyphs('إغلاق مركز الرجاء في سوريا.')];
    @endphp
    <x-contract.dual rowClass="mt-2"
                     :en="collect($EN_END)->map(fn($i) => '- '.$i)->implode('<br>')"
                     :ar="collect($AR_END)->map(fn($i) => $i.$Glyphs('  -'))->implode('<br>')"
    />
</div>


