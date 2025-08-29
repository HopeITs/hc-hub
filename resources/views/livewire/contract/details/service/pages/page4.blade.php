@php
    $empNameEn = $contract->employee?->getTranslation('name', 'en')
        ?? $contract->employee?->name ?? '—';
    $empNameAr = $contract->employee?->getTranslation('name', 'ar')
        ?? $contract->employee?->name ?? '—';

    $EN_FOURTH_TERM_LABEL = '<span class="h-label">Fourth term:</span>';
    $AR_FOURTH_TERM_LABEL = '<span class="h-label">البند الرابع:</span>';

    $EN_FOURTH_3 = 'The first party has the right to terminate this contract at any time before its expiry date in case the second party violates the terms of this contract and the volunteering code of conduct; in this case, all due amounts must be paid to the second party up to the termination date.';
    $AR_FOURTH_3 = 'يحق للفريق الأول إنهاء هذا العقد في أي وقت قبل تاريخ انتهائه في حال خالف الطرف الثاني بنود هذا العقد وقواعد السلوك التطوعي؛ وفي هذه الحالة تُدفع للفريق الثاني جميع المبالغ المستحقة له حتى تاريخ الإنهاء.';

    $EN_FIFTH_TERM_LABEL = '<span class="h-label">Fifth term:</span>';
    $AR_FIFTH_TERM_LABEL = '<span class="h-label">البند الخامس:</span>';

    $EN_FIFTH_TEXT = 'I hereby confirm that I have read and understood the work conditions and all the attached documents and I agree to all of the stated.';
    $AR_FIFTH_TEXT = 'أقر بأنني قرأت وفهمت شروط العمل المنصوص عليها وكافة المستندات المرفقة وأوافق على كل ما ورد فيها.';

    $EN_NOTE_1 = 'This contract was issued on three pages, printed as a single copy, and has only one version secured in the head office in Aleppo.';
    $AR_NOTE_1 = 'صدر هذا العقد على ثلاث صفحات، مطبوعاً على نسخة واحدة محفوظة لدى الإدارة العامة في حلب.';

    $EN_NOTE_2 = 'This contract was issued in Arabic and translated into English.';
    $AR_NOTE_2 = 'صدر هذا العقد بالنسخة العربية وترجم إلى اللغة الإنكليزية.';

    $LEFT_LABEL  = '<span class="h-label">الفريق الثاني/Second Party</span>';
    $RIGHT_LABEL = '<span class="h-label">الفريق الأول/First party</span>';

    $LEFT_SIGN   = '<strong>الاسم والتوقيع/Name & Signature</strong><br>'.e($empNameAr).' / '.e($empNameEn);
    $RIGHT_SIGN  = '<strong>Name & Signature/الاسم والتوقيع</strong><br>Fredy Youssef / فريدي يوسف';

    $LEFT_DATE   = 'التاريخ/date : __________________';
    $RIGHT_DATE  = 'date/التاريخ : __________________';
@endphp

<x-contract.dual rowClass="mt-1" :en="$EN_FOURTH_TERM_LABEL" :ar="$AR_FOURTH_TERM_LABEL"/>

<div class="dual-row mt-2">
    <div class="dual-col dual-en">
        <ol class="list-decimal pl-5 space-y-3" start="3">
            <li>{!! $EN_FOURTH_3 !!}</li>
        </ol>
    </div>
    <div class="dual-col dual-ar">
        <ol class="list-decimal pr-5 space-y-3" start="3" style="direction: rtl; text-align: right;">
            <li>{!! $AR_FOURTH_3 !!}</li>
        </ol>
    </div>
</div>

<x-contract.dual rowClass="mt-4" :en="$EN_FIFTH_TERM_LABEL" :ar="$AR_FIFTH_TERM_LABEL"/>
<x-contract.dual :en="$EN_FIFTH_TEXT" :ar="$AR_FIFTH_TEXT"/>
<x-contract.dual rowClass="mt-2" :en="$EN_NOTE_1" :ar="$AR_NOTE_1"/>
<x-contract.dual :en="$EN_NOTE_2" :ar="$AR_NOTE_2"/>

<div class="dual-row mt-8 font-semibold">
    <div class="dual-col dual-en">{!! $LEFT_LABEL !!}</div>
    <div class="dual-col dual-ar text-right">{!! $RIGHT_LABEL !!}</div>
</div>

<div class="dual-row mt-2">
    <div class="dual-col dual-en">{!! $LEFT_SIGN !!}</div>
    <div class="dual-col dual-ar text-right">{!! $RIGHT_SIGN !!}</div>
</div>

<div class="dual-row mt-8">
    <div class="dual-col dual-en">{!! $LEFT_DATE !!}</div>
    <div class="dual-col dual-ar text-right">{!! $RIGHT_DATE !!}</div>
</div>
