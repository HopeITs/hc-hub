{{-- @php
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $L      = fn (string $t) => '<span class="h-label">'.$t.'</span>';
    $p      = fn (string $t) => '<div>'.$t.'</div>';

    // List helper consistent across view & export
    $asList = function(array $lines, string $lang = 'en') use ($Glyphs) {
        return '<ul style="margin:0; padding-left:14px;">'
            . implode('', array_map(fn($l) => '<li>'.$l.'</li>', $lines))
            . '</ul>';
    };
@endphp


<div class="avoid-break mt-2" >
    <x-contract.dual rowClass="mt-4"
                     :en="$L('Supervision:').$p('Supervision is the responsibility of the Christian Hope Center Syria / Head Office Director. Depending on the circumstances, the Project Director may delegate supervision to another individual.')"
                     :ar="$L($Glyphs('الإشراف:')).$p($Glyphs('هو مسؤولية مدير مركز الرجاء المسيحي في سوريا، وحسب الظروف قد يفوّض مدير المشروع عملية الإشراف لشخص آخر.'))"/>
</div>

<div class="avoid-break mt-4" >
    <x-contract.dual rowClass="mt-6"
                     :en="$L('Annual Leave:')"
                     :ar="$L($Glyphs('إجازات العمل التطوعي:'))"/>
    <x-contract.dual rowClass="mt-2"
                     :en="$p('The volunteer is entitled to 12 days of working days leave during the validity period of the contract, proportional to its duration.')"
                     :ar="$p($Glyphs('يحق للمتطوع 12 يوماً إجازة سنوية خلال فترة سريان العقد، وتكون متناسبة مع مدة العقد.'))"/>
    <x-contract.dual rowClass="mt-2"
                     :en="$p('The volunteer is entitled to 24 days of sickness leave in cases of operations and infectious diseases: 12 days paid and 12 days paid at half the value of the monthly stipend, provided a medical report is submitted.')"
                     :ar="$p($Glyphs('يحق للمتطوع 24 يوماً إجازة مرضية عند الجراحة أو الأمراض المعدية: 12 يوماً مدفوعة و12 يوماً مدفوعة بنصف قيمة المكافأة الشهرية، بشرط تقديم تقرير طبي.'))"/>
    <x-contract.dual rowClass="mt-2"
                     :en="$p('If all annual and sickness leave days are consumed, an additional twelve days may be granted with deduction of their value from the monthly stipend.')"
                     :ar="$p($Glyphs('في حال استهلك المتطوع جميع الإجازات السنوية والمرضية، يُمنح 12 يوماً إضافية ويتم خصم قيمتها من المكافأة الشهرية.'))"/>
</div>

<div class="avoid-break mt-6" >
    <x-contract.dual
        :en="$L('Internal Rules and Regulations:').$p('The volunteer acknowledges and pledges to commit to the following attached documents:')"
        :ar="$L($Glyphs('القوانين والأنظمة الداخلية:')).$p($Glyphs('يقرّ المتطوع ويتعهد بالالتزام بالمستندات المرفقة التالية:'))"/>
    @php
        $EN_LIST = ['Job description.', 'Code of Conduct.', 'Christian Hope Center Policies.'];
        $AR_LIST = [$Glyphs('توصيف العمل التطوعي.'), $Glyphs('القواعد الأخلاقية للتطوع.'), $Glyphs('سياسات مركز الرجاء المسيحي.')];
    @endphp
    <x-contract.dual rowClass="mt-2"
                     :en="$asList($EN_LIST)"
                     :ar="$asList($AR_LIST)"/>
</div>

<div class="avoid-break mt-6" >
    <x-contract.dual
        :en="$L('Expiration of the agreement:').$p('This agreement shall be terminated immediately in the event of:')"
        :ar="$L($Glyphs('انتهاء العقد:')).$p($Glyphs('يعتبر هذا العقد منتهياً في الحالات التالية:'))"/>
    @php
        $EN_END = ['Closure of the Christian Hope Center in Syria.'];
        $AR_END = [$Glyphs('إغلاق مركز الرجاء في سوريا.')];
    @endphp
    <x-contract.dual rowClass="mt-2"
                     :en="$asList($EN_END)"
                     :ar="$asList($AR_END)"/>
</div> --}}



{{-- @php
    use App\\Support\\ArabicText as AR;
    $forPdf = $forPdf ?? false;
    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $L      = fn (string $t) => '<span class="h-label">'.$t.'</span>';
    $p      = fn (string $t) => '<div>'.$t.'</div>';

    $asList = function(array $lines, string $lang = 'en') use ($Glyphs) {
        return '<ul style="margin:0; padding-left:14px;">'
            . implode('', array_map(fn($l) => '<li>'.$l.'</li>', $lines))
            . '</ul>';
    };
@endphp

<div class="avoid-break mt-4">
    <x-contract.dual 
        :en="$L('9. Annual Leave:')" 
        :ar="$L('9. '.$Glyphs('إجازات العمل التطوعي:'))"/>
    
    <x-contract.dual class="mt-1"
        :en="'The second party is entitled to annual leave days during the contract period, proportional to the contract duration.'"
        :ar="$Glyphs('يحق للفريق الثاني يوم إجازة سنوية خلال فترة سريان العقد، وتكون متناسبة مع مدة العقد.')"/>

    <x-contract.dual class="mt-2"
        :en="'The second party is entitled to 24 days of sick leave only when necessary (surgeries and infectious diseases): 12 days fully paid and 12 days paid at half the monthly stipend, provided a medical report is submitted.'"
        :ar="$Glyphs('يحق للفريق الثاني 24 يوم إجازة مرضية عند الضرورة فقط، أي في حالات العمليات والأمراض المعدية، 12 يوم مدفوع و 12 يوم مدفوع بنصف قيمة المكافأة المالية الشهرية وذلك بشرط تقديم تقرير طبي لتبرير الإجازة.')"/>

    <x-contract.dual class="mt-2"
        :en="'If the second party exceeds the specified leave days (annual or sick), an additional leave not exceeding 12 days may be granted, with its value deducted from the monthly stipend.'"
        :ar="$Glyphs('في حال تجاوز الفريق الثاني أيام الاجازات المحددة له والتي تتناسب مع العقد سواءً إجازات عادية أو مرضية، يتم منحه إجازة لا تتجاوز مدتها 12 يوم يتم خصم قيمتها من المكافأة المالية الشهرية المخصصة له.')"/>
</div>

<div class="avoid-break mt-6">
    <x-contract.dual
        :en="$L('10. Internal Rules and Regulations:').$p('The volunteer acknowledges and pledges to commit to the following attached documents:')"
        :ar="$L('10. '.$Glyphs('القوانين والأنظمة الداخلية:')).$p($Glyphs('الفريق الثاني يقر ويتعهد بالاطلاع والالتزام بالمستندات المرفقة التالية:'))"/>
    @php
        $EN_LIST = ['Job description.', 'Code of Conduct.', 'Christian Hope Center Policies.'];
        $AR_LIST = [$Glyphs('توصيف العمل التطوعي.'), $Glyphs('القواعد الأخلاقية للتطوع.'), $Glyphs('سياسات مؤسسة مركز الرجاء المسيحي.')];
    @endphp
    <x-contract.dual rowClass="mt-2" :en="$asList($EN_LIST)" :ar="$asList($AR_LIST)"/>
</div>

<div class="avoid-break mt-6">
    <x-contract.dual
        :en="$L('11. Expiration of the agreement:').$p('This agreement shall be terminated in the following cases:')"
        :ar="$L('11. '.$Glyphs('انتهاء العقد:')).$p($Glyphs('يعتبر هذا العقد منتهياً في الحالات التالية:'))"/>
    @php
        $EN_END = ['Closing of Christian Hope Center in Syria.', 'Project expiration.', 'Expiration of the contract duration.', 'Breach of contract terms or job description after warning.', 'Breach of Code of Conduct after warning.', 'Breach of CHC policies after warning.', 'Unsatisfactory performance evaluation results.'];
        $AR_END = [$Glyphs('إغلاق مؤسسة مركز الرجاء المسيحي في سوريا.'), $Glyphs('انتهاء المشروع.'), $Glyphs('انتهاء مدة العقد.'), $Glyphs('في حال عدم التقيد ببنود هذا العقد وتوصيف العمل المرفق بعد التنبيه والإنذار.'), $Glyphs('في حال المخالفة لمدونة القواعد الأخلاقية بعد التنبيه والإنذار.'), $Glyphs('في حال مخالفة سياسات المؤسسة بعد التنبيه والإنذار.'), $Glyphs('نتائج تقييم الأداء غير المرضية.')];
    @endphp
    <x-contract.dual rowClass="mt-2" :en="$asList($EN_END)" :ar="$asList($AR_END)"/>
</div> --}}