@php
    $L = fn(string $txt) => '<span class="h-label">'.$txt.'</span>';
@endphp

{{-- Supervision --}}
<x-contract.dual rowClass="mt-4"
                 :en="$L('Supervision:').'<br>Supervision is the responsibility of the Christian Hope Center Syria / Head office Director, depending on the circumstance the Project Director may delegate the supervision to another individual.'"
                 :ar="$L('الإشراف:').'<br>هو مسؤولية مدير مركز الرجاء المسيحي في سوريا، وحسب الظروف قد يفوّض مدير المشروع عملية الإشراف لشخص آخر.'"
/>

{{-- Annual Leave --}}
<x-contract.dual rowClass="mt-6"
                 :en="$L('Annual Leave:').'<br>The volunteer is entitled to 12 days of working days leave during the validity period of the contract, with it being proportional to the duration of the contract.'"
                 :ar="$L('إجازات العمل التطوعي:').'<br>يحق للمتطوع 12 يوماً إجازة سنوية خلال فترة سريان العقد، وتكون متناسبة مع مدة العقد.'"
/>

<x-contract.dual rowClass="mt-2"
                 :en="'The volunteer is entitled to 24 days of sickness leave in cases of operations and infectious diseases, 12 days paid and 12 days paid at half the value of the monthly stipend, provided that a medical report is submitted to justify the leave.'"
                 :ar="'يحق للمتطوع 24 يوماً إجازة مرضية عند الجراحة أو الأمراض المعدية، 12 يوم مدفوعة و12 يوم مدفوعة بنصف قيمة المكافأة الشهرية، وذلك بشرط تقديم تقرير طبي لتبرير الإجازة.'"
/>

<x-contract.dual rowClass="mt-2"
                 :en="'In case the volunteer consumes all annual and sickness leave days, he/she will be given twelve days of leave with the deduction of the number of leave days amount from the monthly stipend.'"
                 :ar="'في حال استهلك المتطوع جميع الإجازات السنوية والمرضية المحددة، والتي تتناسب مع مدة سريان العقد، يتم منحه 12 يوماً إجازة، ويتم خصم قيمتها من المكافأة المالية الشهرية.'"
/>

{{-- Internal Rules & Regulations --}}
<x-contract.dual rowClass="mt-6"
                 :en="$L('Internal Rules and Regulations:').'<br>Volunteer acknowledges, and pledges to commit to the following attached documents:'"
                 :ar="$L('القوانين والأنظمة الداخلية:').'<br>المتطوع يقرّ ويتعهد بالالتزام بالمستندات المرفقة التالية:'"
/>

<x-contract.dual rowClass="mt-2"
                 :en="'- Job description.<br>- Code of Conduct.<br>- Christian Hope Center Policies'"
                 :ar="'- توصيف العمل التطوعي.<br>- القواعد الأخلاقية للتطوع.<br>- سياسات مركز الرجاء المسيحي.'"
/>

{{-- Expiration --}}
<x-contract.dual rowClass="mt-6"
                 :en="$L('Expiration of the agreement:').'<br>This agreement shall be terminated immediately in the event of:'"
                 :ar="$L('انتهاء العقد:').'<br>يعتبر هذا العقد منتهياً في الحالات التالية:'"
/>

<x-contract.dual rowClass="mt-2"
                 :en="'- Closure of the Christian Hope Center in Syria.'"
                 :ar="'- إغلاق مركز الرجاء في سوريا.'"
/>
