@php
    $loc   = $locale ?? app()->getLocale();
    $emp   = $contract->employee;

    // Keep name resolution identical to Page 1 (both languages)
    $nameEn = $emp?->getTranslation('name', 'en')
            ?? $emp?->getTranslation('name', $loc)
            ?? $emp?->name
            ?? '—';

    $nameAr = $emp?->getTranslation('name', 'ar')
            ?? $emp?->getTranslation('name', $loc)
            ?? $emp?->name
            ?? '—';

    $esc = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));

    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';
@endphp

{{-- Important note --}}
<x-contract.dual rowClass="mt-2"
                 :en="$L('Important note:').'<br>Hope Center Aleppo / Head office is not obligated to any compensation upon the expiry or termination of the contract.'"
                 :ar="$L('ملاحظة هامة:').'<br>لا يلتزم الفريق الأول بأي تعويضات عند انتهاء عقد المتطوع أو إنهائه.'"
/>

{{-- Acknowledgement --}}
<x-contract.dual rowClass="mt-6"
                 :en="'I hereby certify having read and understood the conditions stipulated above and all the attached documents and confirm that I accept them all.'"
                 :ar="'أقرّ بأني قرأت وفهمت شروط العمل المنصوص عليها وكافة المستندات المرفقة وأوافق عليها كلّها.'"
/>

{{-- Issuance of contract --}}
<x-contract.dual rowClass="mt-6"
                 :en="'This contract was issued on 4 pages, printed on a single copy and kept at the Head Office in Aleppo.<br>The contract is produced in Arabic and translated into English.'"
                 :ar="'صدر هذا العقد على 4 صفحات، مطبوعاً على نسخة واحدة محفوظة لدى الإدارة العامة في حلب.<br>صدر هذا العقد باللغة العربية وترجم إلى اللغة الإنجليزية.'"
/>

{{-- First party (left EN, right AR), same pattern as Page 1 labels --}}
<x-contract.dual rowClass="mt-10"
                 :en="$L('first party').'<br>name and signature<br>Fredy Youssef'"
                 :ar="$L('الفريق الأول').'<br>الاسم والتوقيع<br>فريدي يوسف'"
                 enClass="text-left"
                 arClass="text-right"
/>

{{-- Second party (left EN, right AR), using resolved employee names exactly like Page 1 --}}
<x-contract.dual rowClass="mt-10"
                 :en="$L('second party').'<br>name and signature<br>'.$esc($nameEn)"
                 :ar="$L('الفريق الثاني').'<br>الاسم والتوقيع<br>'.$esc($nameAr)"
                 enClass="text-left"
                 arClass="text-right"
/>

{{-- Date --}}
<x-contract.dual rowClass="mt-8"
                 :en="'date'"
                 :ar="'التاريخ'"
/>
