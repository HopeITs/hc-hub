@php
    $loc           = $locale ?? app()->getLocale();
    $employee      = $contract->employee;
    $employeeName  = $employee?->getTranslation('name', $loc) ?? '—';

    $positionTitle = $contract->position?->requiredPosition?->title
                     ?? $contract->position?->name
                     ?? '#'.$contract->position_id;

    $centerName    = $contract->position?->center?->getTranslation('name', $loc) ?? '—';
    $startDate     = optional($contract->start_date)->format('d/m/Y');
    $endDate       = optional($contract->end_date)->format('d/m/Y');

    $weeklyHours   = (int) ($contract->weekly_hours ?? 40);
    $salaryFormatted = number_format((float) ($contract->salary ?? 0), 0); // “/ 000 USD” style in doc
@endphp

<x-contract.dual
    :en="'whereas “ Christian Hope Center Aleppo / Head office ” has agreed to this volunteer according to the conditions outlined in this contract,'"
    :ar="'و بما أن \" مركز الرجاء المسيحي - حلب - الإدارة العامة \" قد وافق على تطوعه بالشروط المنصوص عليها في هذا العقد,'"
/>

<x-contract.dual rowClass="mt-4"
                 :en="'This contract between the two parties witnesses mutual acceptance of the following:'"
                 :ar="'تم العقد بين الطرفين والقبول المشترك لما يلي:'"
/>

<x-contract.dual rowClass="mt-4"
                 :en="'<span class=\"text-red-600 font-semibold\">Contract type:</span> Volunteer agreement for limited period'"
:ar="'<span class=\"text-red-600 font-semibold\">نوع العقد:</span> عقد تطوعي لمدة محدودة'"
/>

<x-contract.dual rowClass="mt-2"
                 :en="'<span class=\"text-red-600 font-semibold\">Place of Work:</span> Aleppo – Christian Hope Center – Head Office'"
:ar="'<span class=\"text-red-600 font-semibold\">مكان العمل:</span> حلب – مركز الرجاء المسيحي – المكتب'"
/>

<x-contract.dual rowClass="mt-2"
                 :en="'<span class=\"text-red-600 font-semibold\">Job description:</span> ' . e($positionTitle) . ','"
:ar="'<span class=\"text-red-600 font-semibold\">وصف العمل:</span> ' . e($positionTitle) . ','"
/>

<x-contract.dual rowClass="mt-4"
                 :en="'<span class=\"text-red-600 font-semibold\">Duration of the agreement:</span><br>One Year<br><br>Starts from: ' . e($startDate ?: '—') . '<br>And ends on: ' . e($endDate ?: '—')"
:ar="'<span class=\"text-red-600 font-semibold\">مدة العقد:</span><br>سنة<br><br>تبدأ من تاريخ: ' . e($startDate ?: '—') . '<br>وتنتهي في التاريخ: ' . e($endDate ?: '—')"
/>

<x-contract.dual rowClass="mt-4"
                 :en="'<span class=\"text-red-600 font-semibold\">Volunteer Work Hours:</span><br>' .
$weeklyHours . ' working hours per week, 8 hours per day.<br>Standard office hours are Monday to Friday from 9:30 AM to 5:30 PM.<br><br>Saturdays are considered working days when necessary and not treated as extra hours.'"
:ar="'<span class=\"text-red-600 font-semibold\">ساعات العمل التطوعي:</span><br>' .
$weeklyHours . ' ساعة عمل أسبوعياً، 8 ساعات يومياً.<br>الدوام الإداري من الاثنين إلى الجمعة من الساعة 9:30 AM حتى الساعة 5:30 PM.<br><br>يُعتبر يوم السبت يوماً للعمل عند الحاجة، ولا يُعتبر دواماً إضافياً.'"
/>

<x-contract.dual rowClass="mt-4"
                 :en="'<span class=\"text-red-600 font-semibold\">Stipend and Benefits:</span><br>Christian Hope Center is to give a monthly stipend of / ' . $salaryFormatted . ' USD / or its equivalent in Syrian pounds, to be paid in cash to the volunteer at the end of each month.'"
:ar="'<span class=\"text-red-600 font-semibold\">المكافأة المالية والمزايا:</span><br>يمنح مركز الرجاء المسيحي شهرياً للسيد/ة متطوع/ة مكافأة مقدارها / ' . $salaryFormatted . ' / دولار أمريكي أو ما يعادلها بالليرة السورية، تُمنح نقداً في نهاية كل شهر.'"
/>

<x-contract.dual rowClass="mt-4"
                 :en="'<span class=\"text-red-600 font-semibold\">Duties:</span><br>as listed in the job description of the position (attached).'"
:ar="'<span class=\"text-red-600 font-semibold\">المسؤوليات:</span><br>حسب ما ورد في توصيف العمل التطوعي لهذا المنصب (مرفق).'"
/>
