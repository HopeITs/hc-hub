@php
    $loc           = $locale ?? app()->getLocale();
    $employee      = $contract->employee;
    $employeeName  = $employee?->getTranslation('name', $loc) ?? '—';
@endphp

<x-contract.dual
    :en="'<span class=\"text-red-600 font-semibold\">Important note:</span><br>Hope center Aleppo / Head office is not obligated to any compensation upon the expiry or termination of the contract.'"
:ar="'<span class=\"text-red-600 font-semibold\">ملاحظة هامة:</span><br>لا يلتزم الفريق الأول بأي تعويضات عند انتهاء عقد المتطوع أو إنهائه.'"
/>

<x-contract.dual rowClass="mt-6"
                 :en="'I hereby certify having read and understood the conditions stipulated above and all the attached documents and confirm that I accept them all.'"
                 :ar="'أقرّ بأني قرأت وفهمت شروط العمل المنصوص عليها وكافة المستندات المرفقة وأوافق عليها كلّها.'"
/>

<x-contract.dual rowClass="mt-6"
                 :en="'This contract was issued on 4 pages, printed on a single copy and kept at the Head Office in Aleppo.<br>The contract is produced in Arabic and translated into English.'"
                 :ar="'صدر هذا العقد على 4 صفحات، مطبوعاً على نسخة واحدة محفوظة لدى الإدارة العامة في حلب.<br>صدر هذا العقد باللغة العربية وترجم إلى اللغة الإنجليزية.'"
/>

<x-contract.dual rowClass="mt-10"
                 :en="'<span class=\"text-red-600 font-semibold\">first party</span><br>name and signature<br>Fredy Youssef'"
:ar="'<span class=\"text-red-600 font-semibold\">الفريق الأول</span><br>الاسم والتوقيع<br>فريدي يوسف'"
enClass="text-left"
arClass="text-right"
/>

<x-contract.dual rowClass="mt-10"
                 :en="'<span class=\"text-red-600 font-semibold\">second party</span><br>name and signature<br>' . e($employeeName)"
:ar="'<span class=\"text-red-600 font-semibold\">الفريق الثاني</span><br>الاسم والتوقيع<br>' . e($employeeName)"
enClass="text-left"
arClass="text-right"
/>

<x-contract.dual rowClass="mt-8"
                 :en="'date'"
                 :ar="'التاريخ'"
/>
