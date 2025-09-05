@php
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;
    $G = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;

    $L = fn(string $txt) => '<span class="h-label">'.$txt.'</span>';
    $p = fn(string $t) => '<div>'.$t.'</div>';
@endphp

<div class="avoid-break">
    <x-contract.dual rowClass="mt-4"
                     :en="$L('Supervision:').$p('Supervision is the responsibility of the Christian Hope Center Syria / Head Office Director. Depending on the circumstances, the Project Director may delegate supervision to another individual.')"
                     :ar="$L($G('الإشراف:')).$p($G('هو مسؤولية مدير مركز الرجاء المسيحي في سوريا، وحسب الظروف قد يفوّض مدير المشروع عملية الإشراف لشخص آخر.'))"
    />
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-6"
                     :en="$L('Annual Leave:').$p('The volunteer is entitled to 12 working days of leave during the validity period of the contract, proportional to its duration.')"
                     :ar="$L($G('إجازات العمل التطوعي:')).$p($G('يحق للمتطوع 12 يوماً إجازة سنوية خلال فترة سريان العقد، وتكون متناسبة مع مدة العقد.'))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="$p('The volunteer is entitled to 24 days of sickness leave in cases of operations and infectious diseases: 12 days paid and 12 days paid at half the value of the monthly stipend, provided a medical report is submitted.')"
                     :ar="$p($G('يحق للمتطوع 24 يوماً إجازة مرضية عند الجراحة أو الأمراض المعدية: 12 يوماً مدفوعة و12 يوماً مدفوعة بنصف قيمة المكافأة الشهرية، بشرط تقديم تقرير طبي.'))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="$p('If all annual and sickness leave days are consumed, an additional twelve days may be granted with deduction of their value from the monthly stipend.')"
                     :ar="$p($G('في حال استهلك المتطوع جميع الإجازات السنوية والمرضية، يُمنح 12 يوماً إضافية ويتم خصم قيمتها من المكافأة الشهرية.'))"
    />
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-6"
                     :en="$L('Internal Rules and Regulations:').$p('The volunteer acknowledges and pledges to commit to the attached documents:')"
                     :ar="$L($G('القوانين والأنظمة الداخلية:')).$p($G('يقرّ المتطوع ويتعهد بالالتزام بالمستندات المرفقة التالية:'))"
    />
    @php
        $asList = function(array $lines) {
            return '<ul style="margin:0; padding-left:14px;">'
                 . implode('', array_map(fn($l) => '<li>'.$l.'</li>', $lines))
                 . '</ul>';
        };
        $EN_LIST = ['Job description.', 'Code of Conduct.', 'Christian Hope Center Policies.'];
        $AR_LIST = [$G('توصيف العمل التطوعي.'), $G('القواعد الأخلاقية للتطوع.'), $G('سياسات مركز الرجاء المسيحي.')];
    @endphp
    <x-contract.dual rowClass="mt-2" :en="$asList($EN_LIST)" :ar="$asList($AR_LIST)"/>
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-6"
                     :en="$L('Expiration of the agreement:').$p('This agreement shall be terminated immediately in the event of:')"
                     :ar="$L($G('انتهاء العقد:')).$p($G('يعتبر هذا العقد منتهياً في الحالات التالية:'))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="'<ul style=\"margin:0; padding-left:14px;\"><li>Closure of the Christian Hope Center in Syria.</li></ul>'"
    :ar="$G('<ul style=\"margin:0; padding-left:14px;\"><li>إغلاق مركز الرجاء في سوريا.</li></ul>')"
    />
</div>
