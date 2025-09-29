@php
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;
    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';

    $emp = $contract->employee;
    $empNameEn = $emp?->getTranslation('name', 'en') ?? $emp?->name ?? '—';
    $empNameAr = $Glyphs($emp?->getTranslation('name', 'ar') ?? $emp?->name ?? '—');

    $EN_FOURTH_TERM_LABEL = $L('Fourth term:');
    $AR_FOURTH_TERM_LABEL = $L($Glyphs('البند الرابع:'));

    $EN_FOURTH_3 = 'The first party has the right to terminate this contract at any time before its expiry date in case the second party violates the terms of this contract and the volunteering code of conduct; in this case, all due amounts must be paid to the second party up to the termination date.';
    $AR_FOURTH_3 = $Glyphs('يحق للفريق الأول إنهاء هذا العقد في أي وقت قبل تاريخ انتهائه في حال خالف الطرف الثاني بنود هذا العقد وقواعد السلوك التطوعي؛ وفي هذه الحالة تُدفع للفريق الثاني جميع المبالغ المستحقة له حتى تاريخ الإنهاء.');

    $EN_FIFTH_TERM_LABEL = $L('Fifth term:');
    $AR_FIFTH_TERM_LABEL = $L($Glyphs('البند الخامس:'));

    $EN_FIFTH_TEXT = 'I hereby confirm that I have read and understood the work conditions and all the attached documents and I agree to all of the stated.';
    $AR_FIFTH_TEXT = $Glyphs('أقر بأنني قرأت وفهمت شروط العمل المنصوص عليها وكافة المستندات المرفقة وأوافق على كل ما ورد فيها.');

    $EN_NOTE_1 = 'This contract was issued on three pages, printed as a single copy, and has only one version secured in the head office in Aleppo.';
    $AR_NOTE_1 = $Glyphs('صدر هذا العقد على ثلاث صفحات، مطبوعاً على نسخة واحدة محفوظة لدى الإدارة العامة في حلب.');

    $EN_NOTE_2 = 'This contract was issued in Arabic and translated into English.';
    $AR_NOTE_2 = $Glyphs('صدر هذا العقد بالنسخة العربية وترجم إلى اللغة الإنكليزية.');

    $LEFT_LABEL  = $L($Glyphs('الفريق الثاني')).'/Second Party';
    $RIGHT_LABEL = $L($Glyphs('الفريق الأول')).'/First Party';

    $LEFT_SIGN  = '<strong>'.$Glyphs('الاسم والتوقيع').' / Name & Signature</strong><br>'.$empNameAr.' / '.e($empNameEn);
    $RIGHT_SIGN = '<strong>Name & Signature / '.$Glyphs('الاسم والتوقيع').'</strong><br>Fredy Youssef / '.$Glyphs('فريدي يوسف');

    $LEFT_DATE  = $Glyphs('التاريخ').' / date : __________________';
    $RIGHT_DATE = 'date / '.$Glyphs('التاريخ').' : __________________';
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
