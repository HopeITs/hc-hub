@php
    use Illuminate\Support\Carbon;use Illuminate\Support\Str;

    $loc    = $locale ?? app()->getLocale();
    $emp    = $contract->employee;

    $nameEn = $emp?->getTranslation('name', 'en')
           ?? $emp?->getTranslation('name', $loc)
           ?? $emp?->name
           ?? '—';

    $nameAr = $emp?->getTranslation('name', 'ar')
           ?? $emp?->getTranslation('name', $loc)
           ?? $emp?->name
           ?? '—';

    $cityEn = $emp?->city?->getTranslation('name', 'en')
           ?? $emp?->city?->name
           ?? $emp?->place_of_birth
           ?? '';

    $cityAr = $emp?->city?->getTranslation('name', 'ar')
           ?? $emp?->city?->name
           ?? $emp?->place_of_birth
           ?? '';

    $pobEn  = trim((string) $cityEn);
    $pobAr  = trim((string) $cityAr);

    $dob    = $emp?->dob;
    $dobStr = $dob ? Carbon::parse($dob)->format('d/m/Y') : '—';

    $nationalId   = $emp?->national_id;
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
        Volunteer agreement for a limited period
    HTML;

    $AR_TOP = <<<'HTML'
        <span class="h-label">سري وخاص</span><br>
        عقد تطوعي لمدة محدودة
    HTML;

    $EN_FIRST_LABEL = '<span class="h-label">First Party:</span>';
    $AR_FIRST_LABEL = '<span class="h-label">الفريق الأول:</span>';

    $EN_FIRST_1 = 'Christian Hope Center - Aleppo / Head office represented in this Contract by <strong>Mr. Fredy Youssef</strong>';
    $AR_FIRST_1 = ' مركز الرجاء المسيحي - حلب / الإدارة العامة الممثل في هذا العقد ب<strong>السيد فريدي يوسف</strong>';

    $EN_FIRST_2 = '<strong>Mr. Fredy Youssef</strong> is delegated by the Latin church in Syria to manage The Christian Hope Center and all affiliated programs.';
    $AR_FIRST_2 = 'إن السيد فريدي يوسف تم تفويضه من قبل النيابة الرسوليّة اللاتينية في سوريا لإدارة مركز الرجاء المسيحي وكل البرامج التابعة له. <br> <br>';

    $EN_FIRST_3 = 'Hereinafter referred to as “<strong>Christian Hope Center - Aleppo - Head Office</strong>”';
    $AR_FIRST_3 = 'المشار إليه في هذا العقد بـ “<strong>مركز الرجاء المسيحي – حلب - الإدارة العامة</strong>” ';

    $EN_BETWEEN = 'Between';
    $AR_BETWEEN = 'بين';
    $EN_AND     = 'And';
    $AR_AND     = 'و';

    $EN_SECOND_LABEL = '<span class="h-label">Second Party:</span>';
    $AR_SECOND_LABEL = '<span class="h-label">الفريق الثاني:</span>';

    // Name line
    $EN_SECOND_NAME = '<strong>Ms./Mrs.</strong> ' . $esc($nameEn);
    $AR_SECOND_NAME = '<strong>السيد/ة:</strong> ' . $esc($nameAr);

    $EN_SECOND_DOB  = '<strong>Date and Place of Birth:</strong><br>' . $esc($pobEn) . '<br>' . $dobStr;
    $AR_SECOND_DOB  = '<strong>مكان وتاريخ الولادة:</strong><br>' . $esc($pobAr) . '<br>' . $dobStr;

    $EN_SECOND_ID   = '<strong>National ID number:</strong> ' . $esc($nationalId);
    $AR_SECOND_ID   = '<strong>الرقم الوطني:</strong> ' . $esc($nationalId);

    $EN_SECOND_NAT  = '<strong>Nationality:</strong> ' . $esc($natEn);
    $AR_SECOND_NAT  = '<strong>الجنسية:</strong> '   . $esc($natAr);

    $EN_SECOND_ADDR = '<strong>Address:</strong> ' . $esc($addressEn);
    $AR_SECOND_ADDR = '<strong>العنوان:</strong> '  . $esc($addressAr);

    $EN_SECOND_NOTE = 'Hereinafter referred to as “<strong>Volunteer</strong>”';
    $AR_SECOND_NOTE = 'المشار إليه في هذا العقد بـ “<strong>المتطوع</strong>”';

    $EN_PREAMBLE_LABEL = '<span class="h-label">Preamble:</span>';
    $AR_PREAMBLE_LABEL = '<span class="h-label">المقدمة:</span>';

    $EN_PREAMBLE_TEXT = 'Whereas the Volunteer has stated that he/she possesses the required qualifications for <strong>MEAL Manager</strong> (the qualifications are attached to the job description), and on the basis thereof has offered to volunteer for “Christian Hope Center Aleppo / Head office” as a <strong>MEAL Manager</strong>, starting from the signing date of this contract,';

    $AR_PREAMBLE_TEXT = 'بما أن المتطوع قد ذكر أنه يتمتع بالمؤهلات المناسبة ل<strong>مسؤول متابعة وتقييم ومساءلة وتعلم</strong> (المؤهلات مرفقة بتوصيف العمل التطوعي)،'
        .'  <br> <br> <br> وعلى ذلك فقد تقدم للتطوع لدى “مركز الرجاء المسيحي - حلب - الإدارة العامة” بصفة <strong>مسؤول متابعة وتقييم ومساءلة وتعلم</strong> ابتداءً من تاريخ توقيع هذا العقد،';
@endphp

<x-contract.dual tight :en="$EN_TOP" :ar="$AR_TOP" enClass="text-base" arClass="text-base"/>

<x-contract.dual rowClass="mt-4 font-semibold" :en="$EN_BETWEEN" :ar="$AR_BETWEEN"/>

<x-contract.dual rowClass="mt-1" :en="$EN_FIRST_LABEL" :ar="$AR_FIRST_LABEL"/>
<x-contract.dual :en="$EN_FIRST_1" :ar="$AR_FIRST_1"/>
<x-contract.dual :en="$EN_FIRST_2" :ar="$AR_FIRST_2"/>
<x-contract.dual :en="$EN_FIRST_3" :ar="$AR_FIRST_3"/>

<x-contract.dual rowClass="mt-3 font-semibold" :en="$EN_AND" :ar="$AR_AND"/>

<x-contract.dual rowClass="mt-1" :en="$EN_SECOND_LABEL" :ar="$AR_SECOND_LABEL"/>
<x-contract.dual :en="$EN_SECOND_NAME" :ar="$AR_SECOND_NAME"/>
<x-contract.dual :en="$EN_SECOND_DOB" :ar="$AR_SECOND_DOB"/>
<x-contract.dual :en="$EN_SECOND_ID" :ar="$AR_SECOND_ID"/>
<x-contract.dual :en="$EN_SECOND_NAT" :ar="$AR_SECOND_NAT"/>
<x-contract.dual :en="$EN_SECOND_ADDR" :ar="$AR_SECOND_ADDR"/>
<x-contract.dual :en="$EN_SECOND_NOTE" :ar="$AR_SECOND_NOTE"/>

<x-contract.dual rowClass="mt-4" :en="$EN_PREAMBLE_LABEL" :ar="$AR_PREAMBLE_LABEL"/>
<x-contract.dual :en="$EN_PREAMBLE_TEXT" :ar="$AR_PREAMBLE_TEXT"/>
