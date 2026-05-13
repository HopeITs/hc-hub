{{-- @php
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
</div> --}}
{{-- 
@php
    use App\\Support\\ArabicText as AR;
    $forPdf = $forPdf ?? false;
    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $L      = fn (string $t) => '<span class="h-label">'.$t.'</span>';
    $p      = fn (string $t) => '<div>'.$t.'</div>';
    
    $emp = $contract->employee;
    $nameEn = $emp?->name_en ?? '—';
    $nameAr = $emp?->name_ar ?? '—';
    $esc = fn ($v) => e((string)($v ?: '—'));
    $escAr = fn ($v) => e((string)($v ? $Glyphs($v) : '—'));

    $asList = function(array $lines) use ($Glyphs) {
        return '<ul style="margin:0; padding-left:14px;">' . implode('', array_map(fn($l) => '<li>'.$l.'</li>', $lines)) . '</ul>';
    };
@endphp

<div class="avoid-break mt-2">
    <x-contract.dual
        :en="$L('12. Termination of the Contract:').$p('The first party has the right to terminate this contract at any time by informing the second party in writing one month in advance.')"
        :ar="$L('12. '.$Glyphs('إنهاء العقد:')).$p($Glyphs('يحق للفريق الأول إنهاء هذا العقد في أي وقت قبل انتهاء مدته وذلك بإعلام الفريق الثاني خطياً قبل شهر.'))"/>
</div>

<div class="avoid-break mt-4">
    <x-contract.dual
        :en="$L('13. Duties and obligations on extension, expiration, or termination:')"
        :ar="$L('13. '.$Glyphs('الواجبات والالتزامات عند التمديد أو الانتهاء أو إنهاء العقد:'))"/>
    @php
        $EN_OBL = ['First party notifies extension or non-extension one month in advance.', 'Second party notifies resignation one month in advance.', 'Second party returns all equipment and documents upon termination based on the personal handover list.'];
        $AR_OBL = [$Glyphs('يلتزم الفريق الأول عند رغبته بالتمديد أو عدمه بأن يبلغ الفريق الثاني خطياً قبل شهر.'), $Glyphs('يلتزم الفريق الثاني عند رغبته بمغادرة العمل التطوعي بأن يبلغ الفريق الأول خطياً قبل شهر.'), $Glyphs('يلتزم الفريق الثاني عند انتهاء أو إنهاء تطوعه بتسليم جميع التجهيزات والوثائق استناداً لقائمة الاستلام الشخصي.')];
    @endphp
    <x-contract.dual rowClass="mt-2" :en="$asList($EN_OBL)" :ar="$asList($AR_OBL)"/>
</div>

<div class="avoid-break mt-4">
    <x-contract.dual
        :en="$L('14. Special Conditions:').$p('The second party pledges to maintain the confidentiality of all information and documents. The first party has the right to legal action in case of breach.')"
        :ar="$L('14. '.$Glyphs('شروط خاصة:')).$p($Glyphs('يتعهد الفريق الثاني بالحفاظ على سرية جميع المعلومات والوثائق، ويحق للفريق الأول مقاضاة الفريق الثاني في حال إخلاله بهذا البند.'))"/>
</div>

<div class="avoid-break mt-4">
    <x-contract.dual
        :en="$L('15. Legal Status:').$p('This is a voluntary contract and is not subject to Labor Law No. 17 of 2010 or Social Insurance Law No. 92 of 1959. No end-of-service compensation is applicable.')"
        :ar="$L('15. '.$Glyphs('إن هذا العقد هو عقد تطوعي لا يخضع لقانون العمل رقم 17 لعام 2010 أو قانون التأمينات الاجتماعية رقم 92 لعام 1959، ولا يحق للمتطوع المطالبة بتعويض نهاية خدمة.'))"/>
</div>

<div class="avoid-break mt-4">
    <x-contract.dual
        :en="'16. The contract is issued in Arabic and translated into English.'"
        :ar="'16. '.$Glyphs('صدر هذا العقد باللغة العربية وترجم إلى اللغة الإنجليزية.')"/>
    <x-contract.dual class="mt-2"
        :en="'17. This contract was issued on 4 pages and 17 clauses, printed in a single copy kept at the Head Office in Aleppo.'"
        :ar="'17. '.$Glyphs('صدر هذا العقد على 4 صفحات وعلى سبعة عشر بنداً، مطبوعاً على نسخة واحدة محفوظة لدى الإدارة العامة في حلب.')"/>
</div>

<div class="avoid-break mt-6">
    <x-contract.dual
        :en="'I hereby certify having read and understood the conditions stipulated above and all attached documents and confirm my acceptance.'"
        :ar="$Glyphs('أفيد بأنني قرأت وفهمت شروط العمل المنصوص عليها أعلاه وكافة المستندات المرفقة وأقر بموافقتي على كل ما ورد، وعليه أوقع.')"/>
</div>

<div style="margin-top: 40px; position: relative; height: 100px;">
    <div style="position: absolute; left: 0; text-align: center; width: 45%;">
        <div style="font-weight:bold; color:#b00;">Second Party / {{ $Glyphs('الفريق الثاني') }}</div>
        <div style="margin-top: 5px;">{{ $esc($nameEn) }} / {{ $escAr($nameAr) }}</div>
    </div>
    <div style="position: absolute; right: 0; text-align: center; width: 45%;">
        <div style="font-weight:bold; color:#b00;">First Party / {{ $Glyphs('الفريق الأول') }}</div>
        <div style="margin-top: 5px;">{{ $Glyphs('فريدي يوسف') }}</div>
    </div>
</div> --}}