@php
    use Illuminate\Support\Carbon;

    $loc = $locale ?? app()->getLocale();
    $emp = $contract->employee;

    $nameEn = $emp?->getTranslation('name', 'en')
            ?? $emp?->getTranslation('name', $loc)
            ?? $emp?->name ?? '—';
    $nameAr = $emp?->getTranslation('name', 'ar')
            ?? $emp?->getTranslation('name', $loc)
            ?? $emp?->name ?? '—';

    $cityEn = $emp?->city?->getTranslation('name', 'en')
            ?? $emp?->city?->name
            ?? $emp?->place_of_birth ?? '';
    $cityAr = $emp?->city?->getTranslation('name', 'ar')
            ?? $emp?->city?->name
            ?? $emp?->place_of_birth ?? '';
    $pobEn = trim((string) $cityEn);
    $pobAr = trim((string) $cityAr);

    $dob = $emp?->dob;
    $dobStr = $dob ? Carbon::parse($dob)->format('d/m/Y') : '—';

    $nationalId = $emp?->national_id;
    $nationalityRaw = (string) ($emp?->nationality ?? '');

    $addressEn = $emp?->getTranslation('address', 'en')
        ?? $emp?->getTranslation('address', $loc)
        ?? (is_array($emp?->address ?? null) ? ($emp->address['en'] ?? reset($emp->address) ?? '') : ($emp->address ?? ''));
    $addressAr = $emp?->getTranslation('address', 'ar')
        ?? $emp?->getTranslation('address', $loc)
        ?? (is_array($emp?->address ?? null) ? ($emp->address['ar'] ?? reset($emp->address) ?? '') : ($emp->address ?? ''));

    $esc = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));

    $natKey = strtoupper(trim((string) $nationalityRaw));
    $NATIONALITY_MAP = [
        '1'   => ['en' => 'Syrian', 'ar' => 'سوري'],
        'SY'  => ['en' => 'Syrian', 'ar' => 'سوري'],
        'SYR' => ['en' => 'Syrian', 'ar' => 'سوري'],
    ];
    $natEn = $NATIONALITY_MAP[$natKey]['en'] ?? (string)($nationalityRaw ?: '—');
    $natAr = $NATIONALITY_MAP[$natKey]['ar'] ?? (string)($nationalityRaw ?: '—');


    $EN_TOP = <<<'HTML'
        <span class="h-label">PRIVATE AND CONFIDENTIAL</span><br>
        Volunteering contract for a limited period
    HTML;

    $AR_TOP = <<<'HTML'
        <span class="h-label">سري وخاص</span><br>
        عقد تطوعي لمدة محدودة
    HTML;

    $EN_BETWEEN = 'Between';
    $AR_BETWEEN = 'بين';

    $EN_AND = 'and';
    $AR_AND = 'و';

    $EN_FIRST_LABEL = '<span class="h-label">First Party:</span>';
    $AR_FIRST_LABEL = '<span class="h-label">الفريق الأول:</span>';

    $EN_FIRST_1 = 'Christian Hope Center Aleppo - Head office represented in this Contract by Mr. Fredy Youssef';
    $AR_FIRST_1 = 'مركز الرجاء المسيحي - حلب - الإدارة العامة الممثل في هذا العقد بالسيد فريدي يوسف';

    $EN_FIRST_2 = 'Mr. Fredy Youssef is delegated by the Latin church in Syria to manage The Christian Hope Center project and all affiliated programs.';
    $AR_FIRST_2 = 'إن السيد فريدي يوسف تم تفويضه من قبل النيابة الرسولية اللاتينية في سوريا لإدارة مشروع مركز الرجاء وكل البرامج التابعة له.';

    $EN_SECOND_LABEL = '<span class="h-label">Second Party:</span>';
    $AR_SECOND_LABEL = '<span class="h-label">الفريق الثاني:</span>';

    $EN_SECOND_NAME = '<strong>Ms./Mrs.:</strong> ' . $esc($nameEn);
    $AR_SECOND_NAME = '<strong>السيد/ة:</strong> ' . $esc($nameAr);

    $EN_SECOND_ID   = '<strong>National ID number:</strong> ' . $esc($nationalId);
    $AR_SECOND_ID   = '<strong>الرقم الوطني:</strong> ' . $esc($nationalId);

    $EN_SECOND_DOB  = '<strong>Date and Place of Birth:</strong> ' . ($pobEn !== '' ? $esc($pobEn) . ' — ' : '') . $dobStr;
    $AR_SECOND_DOB  = '<strong>مكان وتاريخ الولادة:</strong> ' . ($pobAr !== '' ? $esc($pobAr) . ' — ' : '') . $dobStr;

    $EN_SECOND_NAT  = '<strong>Nationality:</strong> ' . $esc($natEn);
    $AR_SECOND_NAT  = '<strong>الجنسية:</strong> '   . $esc($natAr);

    $EN_SECOND_ADDR = '<strong>Address:</strong> ' . $esc($addressEn);
    $AR_SECOND_ADDR = '<strong>العنوان:</strong> '  . $esc($addressAr);

    $EN_CONTRACT_INTRO_LABEL = '<span class="h-label">Contract Introduction:</span>';
    $AR_CONTRACT_INTRO_LABEL = '<span class="h-label">مقدمة العقد:</span>';

    $EN_CONTRACT_INTRO_TEXT = 'The "First Party" wants to utilize the expertise of the second party as a MEAL Officer and offered the "second party" this position whereas the "second party" has agreed to this offer.';
    $AR_CONTRACT_INTRO_TEXT = 'يرغب "الفريق الأول" في الاستفادة من خبرة الفريق الثاني بصفة مسؤول MEAL وقد عرض هذه المهمة على "الفريق الثاني" والذي بدوره وافق على هذا العرض.';

    $EN_FIRST_TERM_LABEL = '<span class="h-label">First term:</span>';
    $AR_FIRST_TERM_LABEL = '<span class="h-label">البند الأول:</span>';

    $EN_FIRST_TERM_TEXT = 'The introduction of this contract is considered an indissoluble part of it.';
    $AR_FIRST_TERM_TEXT = 'تُعتبر مقدمة هذا العقد جزءاً لا يتجزأ من هذا العقد.';

    $EN_SECOND_TERM_LABEL = '<span class="h-label">Second term:</span>';
    $AR_SECOND_TERM_LABEL = '<span class="h-label">البند الثاني:</span>';

    $EN_SECOND_TERM_TEXT = 'The second party is committed to implementing all volunteering activities agreed on with the first party (attached with the job description).';
    $AR_SECOND_TERM_TEXT = 'يلتزم الفريق الثاني بالقيام بكافة الأعمال التطوعية المتفق عليها مع الفريق الأول (مرفقة بتوصيف العمل التطوعي).';
@endphp

<div class="text-center mb-3">
    <h2 class="text-[18px] font-bold">عقد اتفاق تجريبي</h2>
</div>

<x-contract.dual tight :en="$EN_TOP" :ar="$AR_TOP" enClass="text-base" arClass="text-base"/>

<x-contract.dual rowClass="mt-4 font-semibold" :en="$EN_BETWEEN" :ar="$AR_BETWEEN"/>

<x-contract.dual rowClass="mt-1" :en="$EN_FIRST_LABEL" :ar="$AR_FIRST_LABEL"/>
<x-contract.dual :en="$EN_FIRST_1" :ar="$AR_FIRST_1"/>
<x-contract.dual :en="$EN_FIRST_2" :ar="$AR_FIRST_2"/>

<x-contract.dual rowClass="mt-3 font-semibold" :en="$EN_AND" :ar="$AR_AND"/>

<x-contract.dual rowClass="mt-1" :en="$EN_SECOND_LABEL" :ar="$AR_SECOND_LABEL"/>
<x-contract.dual :en="$EN_SECOND_NAME" :ar="$AR_SECOND_NAME"/>
<x-contract.dual :en="$EN_SECOND_ID"   :ar="$AR_SECOND_ID"/>
<x-contract.dual :en="$EN_SECOND_DOB"  :ar="$AR_SECOND_DOB"/>
<x-contract.dual :en="$EN_SECOND_NAT"  :ar="$AR_SECOND_NAT"/>
<x-contract.dual :en="$EN_SECOND_ADDR" :ar="$AR_SECOND_ADDR"/>

<x-contract.dual rowClass="mt-4" :en="$EN_CONTRACT_INTRO_LABEL" :ar="$AR_CONTRACT_INTRO_LABEL"/>
<x-contract.dual :en="$EN_CONTRACT_INTRO_TEXT" :ar="$AR_CONTRACT_INTRO_TEXT"/>

<x-contract.dual rowClass="mt-3" :en="$EN_FIRST_TERM_LABEL" :ar="$AR_FIRST_TERM_LABEL"/>
<x-contract.dual :en="$EN_FIRST_TERM_TEXT" :ar="$AR_FIRST_TERM_TEXT"/>

<x-contract.dual rowClass="mt-3" :en="$EN_SECOND_TERM_LABEL" :ar="$AR_SECOND_TERM_LABEL"/>
<x-contract.dual :en="$EN_SECOND_TERM_TEXT" :ar="$AR_SECOND_TERM_TEXT"/>
