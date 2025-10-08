@php
    $EN_FIFTH_TERM_LABEL = '<span class="h-label">Fifth term:</span>';
    $AR_FIFTH_TERM_LABEL = '<span class="h-label">البند الخامس:</span>';

    $EN_FIFTH_TEXT = 'I hereby confirm that I have read and understood the work conditions and all the attached documents and I agree to all of the stated.';
    $AR_FIFTH_TEXT = 'أقر بأنني قرأت وفهمت شروط العمل المنصوص عليها وكافة المستندات المرفقة وأوافق بموجبها على كل ما ورد.';

    $EN_NOTE_1 = 'This contract was issued on three pages, printed as a single copy, and has only one version secured in the head office in Aleppo.';
    $AR_NOTE_1 = 'صدر هذا العقد على ثلاث صفحات، مطبوعاً على نسخة واحدة محفوظة لدى الإدارة العامة في حلب.';

    $EN_NOTE_2 = 'This contract was issued in Arabic and translated into English.';
    $AR_NOTE_2 = 'صدر هذا العقد بالنسخة العربية وترجم إلى اللغة الإنكليزية.';

    $EN_FIRST_PARTY_LABEL  = '<span class="h-label">first party</span>';
    $AR_FIRST_PARTY_LABEL  = '<span class="h-label">الفريق الأول</span>';

    $EN_SECOND_PARTY_LABEL = '<span class="h-label">second party</span>';
    $AR_SECOND_PARTY_LABEL = '<span class="h-label">الفريق الثاني</span>';

    $empNameEn = $emp?->name_en ?? '—';
    $empNameAr = $emp?->name_ar ?? '—';

    $EN_FIRST_SIGN = '<strong>Name and Signature</strong><br>Fredy Youssef';
    $AR_FIRST_SIGN = '<strong>الاسم والتوقيع</strong><br>فريدي يوسف';

    $EN_SECOND_SIGN = '<strong>Name and Signature</strong><br>' . e($empNameEn);
    $AR_SECOND_SIGN = '<strong>الاسم والتوقيع</strong><br>' . e($empNameAr);

    $EN_DATE_LABEL = '<strong>date</strong>';
    $AR_DATE_LABEL = '<strong>التاريخ</strong>';
@endphp

<x-contract.dual rowClass="mt-1" :en="$EN_FIFTH_TERM_LABEL" :ar="$AR_FIFTH_TERM_LABEL"/>
<x-contract.dual :en="$EN_FIFTH_TEXT" :ar="$AR_FIFTH_TEXT"/>
<x-contract.dual rowClass="mt-2" :en="$EN_NOTE_1" :ar="$AR_NOTE_1"/>
<x-contract.dual :en="$EN_NOTE_2" :ar="$AR_NOTE_2"/>

<div class="dual-row mt-6 font-semibold">
    <div class="dual-col dual-en">{!! $EN_FIRST_PARTY_LABEL !!}</div>
    <div class="dual-col dual-ar text-right">{!! $AR_FIRST_PARTY_LABEL !!}</div>
</div>
<div class="dual-row mt-2">
    <div class="dual-col dual-en">{!! $EN_FIRST_SIGN !!}</div>
    <div class="dual-col dual-ar text-right">{!! $AR_FIRST_SIGN !!}</div>
</div>

<div class="dual-row mt-6 font-semibold">
    <div class="dual-col dual-en">{!! $EN_SECOND_PARTY_LABEL !!}</div>
    <div class="dual-col dual-ar text-right">{!! $AR_SECOND_PARTY_LABEL !!}</div>
</div>
<div class="dual-row mt-2">
    <div class="dual-col dual-en">{!! $EN_SECOND_SIGN !!}</div>
    <div class="dual-col dual-ar text-right">{!! $AR_SECOND_SIGN !!}</div>
</div>

<div class="dual-row mt-6">
    <div class="dual-col dual-en">{!! $EN_DATE_LABEL !!}: __________________</div>
    <div class="dual-col dual-ar text-right">{!! $AR_DATE_LABEL !!}: __________________</div>
</div>
