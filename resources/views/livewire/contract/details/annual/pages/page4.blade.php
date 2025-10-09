@php
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $L      = fn (string $t) => '<span class="h-label">'.$t.'</span>';
    $p      = fn (string $t) => '<div>'.$t.'</div>';

    // Unified bullet list renderer (handles EN/AR properly)
    $asList = fn (array $lines, string $lang = 'en') =>
        '<ul style="margin:0; padding-left:0; padding-right:0; list-style:none;">'
        . implode('', array_map(function($l) use ($lang) {
            if ($lang === 'ar') {
                return '<li style="direction:rtl; text-align:right; position:relative; padding-right:12px;">'
                     . '<span style="position:absolute; right:0;">-</span>'.$l.'</li>';
            }
            return '<li style="position:relative; padding-left:12px;">'
                 . '<span style="position:absolute; left:0;">-</span>'.$l.'</li>';
        }, $lines))
        . '</ul>';

    // Reasons
    $EN_REASONS = [
        'Project expiration.',
        'Expiration of the agreement.',
        'Any breach of this agreement and the attached job description by the volunteer after notice and warning.',
        'Breaching the Code of Conduct after notice and warning.',
        'Breaching the Christian Hope Center policies and bylaws after notice and warning.',
        'Unsatisfactory results of job performance evaluation.',
    ];
    $AR_REASONS = [
        $Glyphs('انتهاء المشروع.'),
        $Glyphs('انتهاء مدة العقد.'),
        $Glyphs('عدم الالتزام ببنود هذا العقد وتوصيف العمل التطوعي المرفق من قبل المتطوع بعد التنبيه والإنذار.'),
        $Glyphs('مخالفة القواعد الأخلاقية للتطوع بعد التنبيه والإنذار.'),
        $Glyphs('مخالفة سياسات مركز الرجاء المسيحي بعد التنبيه والإنذار.'),
        $Glyphs('نتائج تقييم الأداء غير المرضية.'),
    ];

    // Notices
    $EN_NOTICES = [
        'Hope Center Aleppo / Head office will notify the volunteer through a written notice of the termination or extension date one month in advance.',
        'The volunteer will notify Hope Center Aleppo / Head office of his/her wish to end the volunteering one month in advance.',
    ];
    $AR_NOTICES = [
        $Glyphs('يُعلم الفريق الأول المتطوع خطياً بإنهاء العقد أو تمديده قبل مدة شهر.'),
        $Glyphs('يُعلم المتطوع الفريق الأول برغبته بإنهاء التطوع قبل مدة شهر.'),
    ];

    // Return obligations
    $EN_RETURN = 'The volunteer is committed to deliver to “Christian Hope Center” all equipment, materials, and documents received upon termination or expiration of this contract, based on the volunteer’s personal receipt list (updated periodically).';
    $AR_RETURN = $Glyphs('يلتزم الفريق الثاني (المتطوع) عند انتهاء أو إنهاء العقد بتسليم جميع العهد والأدوات والمستندات التي استلمها إلى مركز الرجاء المسيحي، وذلك استنادًا لقائمة الاستلام الشخصية لكل متطوع والتي يتم تحديثها دوريًا.');
@endphp


<div class="avoid-break" >
    <x-contract.dual
        :en="$L('Reasons for Termination:')"
        :ar="$L($Glyphs('أسباب إنهاء العقد:'))"/>
    <x-contract.dual rowClass="mt-2"
                     :en="$asList($EN_REASONS,'en')"
                     :ar="$asList($AR_REASONS,'ar')"/>
</div>

<div class="avoid-break mt-4">
    <x-contract.dual
        :en="$L('Termination of the Contract:').$p('Hope Center Aleppo / Head office has the right to terminate this contract during the effective period, by informing the volunteer through a written termination notice one month in advance.')"
        :ar="$L($Glyphs('إنهاء العقد:')).$p($Glyphs('يحق للفريق الأول إنهاء هذا العقد في أي وقت قبل انتهاء مدته وذلك بإعلام الفريق الثاني خطياً قبل شهر.'))"/>
</div>

<div class="avoid-break mt-4" >
    <x-contract.dual
        :en="$L('Duties and obligations on extension, expiration, or termination:')"
        :ar="$L($Glyphs('الواجبات والالتزامات عند التمديد أو الانتهاء أو إنهاء العقد:'))"/>
    <x-contract.dual rowClass="mt-2"
                     :en="$asList($EN_NOTICES,'en')"
                     :ar="$asList($AR_NOTICES,'ar')"/>
    <x-contract.dual rowClass="mt-4"
                     :en="$p($EN_RETURN)"
                     :ar="$p($AR_RETURN)"/>
</div>
