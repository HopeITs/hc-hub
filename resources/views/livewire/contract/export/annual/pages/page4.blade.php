{{-- @php
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';
    $p = fn (string $t) => '<div>'.$t.'</div>';

   $asList = fn (array $lines, string $lang = 'en') =>
    '<ul style="margin:0; padding-left:0; padding-right:0; list-style:none;">'
    . implode('', array_map(function($l) use ($lang) {
        if ($lang === 'ar') {
            return '<li style="direction:rtl; text-align:right; position:relative; padding-right:12px;">'
                 . '<span style="position:absolute; right:0;">-</span>'
                 . $l
                 . '</li>';
        }
        return '<li style="position:relative; padding-left:12px;">'
             . '<span style="position:absolute; left:0;">-</span>'
             . $l
             . '</li>';
    }, $lines))
    . '</ul>';


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
        $Glyphs('في حال عدم التقيد ببنود هذا العقد وتوصيف العمل التطوعي المرفق من قبل المتطوع بعد التنبيه والإنذار'),
        $Glyphs('في حال المخالفة لمدونة القواعد الأخلاقية للتطوع بعد التنبيه والإنذار.'),
        $Glyphs('في حال مخالفة سياسات مركز الرجاء المسيحي بعد التنبيه والإنذار.'),
        $Glyphs('نتائج تقييم الأداء غير المرضية.'),
    ];

    $EN_NOTICES = [
        'Hope Center Aleppo / Head office will notify the volunteer through a written notice of the termination or extension date one month in advance.',
        'The volunteer will notify Hope Center Aleppo / Head office of his/her wish to end the volunteering one month in advance.',
    ];
    $AR_NOTICES = [
        $Glyphs('يلتزم الفريق الأول عند رغبته بالتمديد أو عدمه بأن  <br> يُبلّغ المتطوّع بذلك بشكل خطي قبل مدة شهر.'),
        $Glyphs(__('general.text31')),
    ];

    $EN_RETURN = '- The volunteer is committed to deliver to “Christian Hope Center” all equipment, materials, and documents received upon termination or expiration of this contract, based on the volunteer’s personal receipt list (updated periodically).';
    $AR_RETURN = $Glyphs(__('general.text32'));
@endphp

<div class="avoid-break mt-2" style="line-height: 0.8">
    <x-contract.dual
        :en="$L('Reasons for Termination:')"
        :ar="$L($Glyphs('أسباب إنهاء العقد:'))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="$asList($EN_REASONS,'en')"
                     :ar="$asList($AR_REASONS,'ar')"
    />
</div>

<div class="avoid-break mt-6" style="line-height: 0.8">
    <x-contract.dual
        :en="$L('Termination of the Contract:').$p('Hope Center Aleppo / Head office has the right to terminate this contract during the effective period, by informing the volunteer through a written termination notice one month in advance.')"
        :ar="$L($Glyphs('إنهاء العقد:')).$p($Glyphs('يحق للفريق الأول إنهاء هذا العقد في أي وقت قبل <br> انتهاء مدته وذلك بإعلام الفريق الثاني <br> خطياً قبل شهر.'))"
    />
</div>

<div class="avoid-break mt-6" style="line-height: 0.8">
    <x-contract.dual
        :en="$L('Duties and obligations on extension, expiration, or termination of the agreement:')"
        :ar="$L($Glyphs('الواجبات والالتزامات على تمديد أو انتهاء أو إنهاء العقد:'))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="$asList($EN_NOTICES,'en')"
                     :ar="$asList($AR_NOTICES,'ar')"
    />
    <x-contract.dual rowClass="mt-4"
                     :en="$p($EN_RETURN)"
                     :ar="$p($AR_RETURN)"
    />
</div> --}}



@php
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $SmartGlyphs = fn ($s, $limit = 2000) => $forPdf ? AR::smartGlyphs((string)$s, $limit) : (string)$s;
    $LB     = fn ($t) => '<span class="h-label-bold">' . $t . '</span>';

    $emp    = $contract->employee;

    $nameEn = trim(($emp?->first_name_en ?? '') . ' ' . ($emp?->father_name_en ?? '') . ' ' . ($emp?->last_name_en ?? ''));
    $nameEn = $nameEn !== '' ? $nameEn : '—';

    $nameAr = trim(($emp?->first_name_ar ?? '') . ' ' . ($emp?->father_name_ar ?? '') . ' ' . ($emp?->last_name_ar ?? ''));
    $nameAr = $nameAr !== '' ? $nameAr : '—';

    $esc   = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));
    $escAr = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));

    $ar_term_15 = 
        '<strong>' . $Glyphs('لا يخضع لقانون العمل') . '</strong> ' . $Glyphs('هو عقد تطوّعي') . ' ' . $LB($Glyphs('إن هذا العقد') . ' .15') . '<br>' .
        $Glyphs('أو أي تعديل للقانون أو أي قانون') . ' <strong>' . $Glyphs('رقم /17/ لعام 2010') . '</strong><br>' .
        '<strong>92 ' . $Glyphs('لا يخضع لقانون التأمينات الاجتماعية رقم') . '</strong> ' . $Glyphs('جديد، وكذلك') . '<br>' .
        $Glyphs('ولا يحق للفريق الثاني المطالبة') . ' <strong>' . $Glyphs('لعام 1959 وتعديلاته،') . '</strong><br>' .
        $Glyphs('بأي تعويض نهاية خدمة عند انتهاء عقد التطوّع أو إنهائه.');
@endphp

<div style="line-height: 1.15;">
    <div class="avoid-break" style="margin-top: 10px;">
        <x-contract.dual rowClass="lh-tight"
            :en="$LB('14. Special conditions:')" 
            :ar="$LB($Glyphs('شروط خاصة:') . ' .14')" />
        <x-contract.dual class="mt-1 lh-tight"
            :en="'The second team commits to maintaining the confidentiality of all information and documents they have access to, and not using them outside the scope of the volunteer work. The first team has the right to take legal action against the second team in case of a breach of this clause.'"
            :ar="
                $SmartGlyphs('يتعهد الفريق الثاني بالحفاظ على سريّة جميع المعلومات') . '<br>' .
                $SmartGlyphs('والوثائق التي يطّلّع عليها، وعدم استخدامها خارج نطاق العمل') . '<br>' .
                $SmartGlyphs('التطوّعي. ويحق للفريق الأول مقاضاة الفريق الثاني في حال') . '<br>' .
                $SmartGlyphs('إخلاله بهذا البند.')
            " />
    </div>

    <div class="avoid-break" style="margin-top: 15px;">
        <x-contract.dual rowClass="lh-tight"
            :en="$LB('15. This contract') . ' is a voluntary contract and is <strong>not subject to Labor Law No. /17/ of 2010</strong> or any amendments to the law or any new law, and it is also <strong>not subject to social security Law No. 92 of 1959 and its amendments</strong>. The second team has no right to claim any end-of-service compensation upon the expiration or termination of the second party contract.'"
            :ar="$ar_term_15" 
        />
    </div>

    <div class="avoid-break" style="margin-top: 15px;">
        <x-contract.dual rowClass="lh-tight"
            :en="$LB('16. The contract is issued') . ' in Arabic and translated into English.'"
            :ar="$SmartGlyphs('باللغة العربية وتُرجم إلى اللغة الإنكليزية.') . ' ' . $LB($Glyphs('صدر هذا العقد') . ' .16')" />
    </div>

    <div class="avoid-break" style="margin-top: 15px;">
        <x-contract.dual rowClass="lh-tight"
            :en="$LB('17. This contract was issued') . ' on 4 pages and contains 17 clauses, printed in a single copy preserved at the Head Office in Aleppo.'"
            :ar="
                $SmartGlyphs('على 4 صفحات وعلى سبعة عشر بنداً،') . ' ' . $LB($Glyphs('صدر هذا العقد') . ' .17') . '<br>' .
                $SmartGlyphs('مطبوعاً على نسخة واحدة محفوظة لدى الإدارة') . '<br>' .
                $SmartGlyphs('العامة في حلب.')
            " />
            
    </div>

    <div class="avoid-break" style="margin-top: 25px;">
        <x-contract.dual rowClass="lh-tight"
            :en="'I hereby certify having read and understood the conditions stipulated above and all the attached documents and confirm that I accept them all.'"
            :ar="
                $SmartGlyphs('أفيد بأنني قرأت وفهمت شروط العمل المنصوص عليها أعلاه') . '<br>' .
                $SmartGlyphs('وكافة المستندات المرفقة وأقر بموافقتي على كل ما ورد،') . '<br>' .
                $SmartGlyphs('وعليه أوقّع.')
            " />
    </div>

    <div class="avoid-break " style="position: relative; margin-top: 45px; min-height: 220px;">
        
        <div style="position: absolute; left: 0; text-align: center; width: 48%;">
            <div style="margin-bottom: 12px; font-size: 12pt;">
                {!! $LB('Second Party') !!} / {!! $LB($SmartGlyphs('الفريق الثاني')) !!}
            </div>
            <div style="margin-bottom: 15px; font-size: 10pt;">
                Name and Signature / {{ $SmartGlyphs('الاسم والتوقيع') }}
            </div>
            <div style="line-height: 1.6; margin-bottom: 25px; font-size: 11pt;">
                <strong>{{ $SmartGlyphs($escAr($nameAr)) }}</strong>
                <br>
                <strong>{{ $esc($nameEn) }}</strong>
            </div>
            <div style="font-size: 10pt;">
                Date / {{ $SmartGlyphs('التاريخ') }}
            </div>
        </div>

        <div style="position: absolute; right: 0; text-align: center; width: 48%;">
            <div style="margin-bottom: 12px; font-size: 12pt;">
                {!! $LB('First Party') !!} / {!! $LB($SmartGlyphs('الفريق الأول')) !!}
            </div>
            <div style="margin-bottom: 15px; font-size: 10pt;">
                Name and Signature / {{ $SmartGlyphs('الاسم والتوقيع') }}
            </div>
            <div style="line-height: 1.6; margin-bottom: 25px; font-size: 11pt;">
                <strong>{{ $SmartGlyphs('فريدي جرجس يوسف') }}</strong>
                <br>
                <strong>Fredy Georges Youssef</strong>
            </div>
            <div style="font-size: 10pt;">
                Date / {{ $SmartGlyphs('التاريخ') }}
            </div>
        </div>

    </div>
</div>