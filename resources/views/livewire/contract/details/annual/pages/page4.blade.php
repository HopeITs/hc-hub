@php
    $L = fn(string $t) => '<span class="h-label">'.$t.'</span>';
@endphp

{{-- Reasons for expiration/termination (bulleted) --}}
@php
    $EN_REASONS = implode('<br>', [
        '- Project expiration.',
        '- Expiration of the agreement.',
        '- Any breach of this agreement and the attached job description by the volunteer after notice and warning.',
        '- Breaching the Code of Conduct after notice and warning.',
        '- Breaching the Christian Hope Center policies and bylaws after notice and warning.',
        '- Unsatisfactory results of job performance evaluation.',
    ]);

    $AR_REASONS = implode('<br>', [
        '- انتهاء المشروع.',
        '- انتهاء مدة العقد.',
        '- في حال عدم الالتزام ببنود هذا العقد وتوصيف العمل التطوعي المرفق من قبل المتطوع بعد التنبيه والإنذار.',
        '- في حال مخالفة القواعد الأخلاقية للتطوع بعد التنبيه والإنذار.',
        '- في حال مخالفة سياسات مركز الرجاء المسيحي بعد التنبيه والإنذار.',
        '- نتائج تقييم الأداء غير المرضية.',
    ]);
@endphp

<x-contract.dual rowClass="mt-2" :en="$EN_REASONS" :ar="$AR_REASONS"/>

{{-- Termination of the Contract --}}
<x-contract.dual rowClass="mt-6"
                 :en="$L('Termination of the Contract:').'<br>Hope Center Aleppo / Head office has the right to terminate this contract during the effective period, by informing the volunteer through a written termination notice one month in advance.'"
                 :ar="$L('إنهاء العقد:').'<br>يحق للفريق الأول إنهاء هذا العقد في أي وقت قبل انتهاء مدته وذلك بإعلام الفريق الثاني خطيًا قبل شهر.'"
/>

{{-- Duties & obligations on extension/expiration/termination --}}
<x-contract.dual rowClass="mt-6"
                 :en="$L('Duties and obligations on the extension, expiration, or termination of the agreement:')"
                 :ar="$L('الواجبات والالتزامات على التمديد أو الانتهاء أو إنهاء العقد:')"
/>

@php
    $EN_NOTICES = implode('<br>', [
        '- Hope Center Aleppo / Head office will notify the volunteer through a written notice of the termination or extension date one month in advance.',
        '- The volunteer will notify Hope Center Aleppo / Head office of his/her wish to end the volunteering one month in advance.',
    ]);

    $AR_NOTICES = implode('<br>', [
        '- يُعلم الفريق الأول المتطوع خطيًا بإنهاء العقد أو تمديده قبل مدة شهر.',
        '- يُعلم المتطوع الفريق الأول برغبته بإنهاء التطوع قبل مدة شهر.',
    ]);
@endphp

<x-contract.dual rowClass="mt-2" :en="$EN_NOTICES" :ar="$AR_NOTICES"/>

@php
    $EN_RETURN = '- The volunteer is committed to deliver to “Christian Hope Center” all the equipment, materials, and documents that he/she received upon the termination or expiration of this contract based on the personal receipt list of the volunteer, which is updated periodically.';
    $AR_RETURN = '- يلتزم الفريق الثاني (المتطوع) عند انتهاء أو إنهاء العقد بتسليم جميع العهد والأدوات والمستندات التي استلمها إلى مركز الرجاء المسيحي، وذلك استنادًا لقائمة الاستلام الشخصية لكل متطوع والتي يتم تحديثها دوريًا.';
@endphp

<x-contract.dual rowClass="mt-4" :en="$EN_RETURN" :ar="$AR_RETURN"/>
