{{-- @php
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';
    $p = fn (string $t) => '<div>'.$t.'</div>';
    $asList = fn (array $lines) => '<ul style="margin:0; padding-left:14px;">'.implode('', array_map(fn($l) => '<li>'.$l.'</li>', $lines)).'</ul>';
@endphp

<div class="avoid-break mt-2" style="line-height: 0.8">
    <x-contract.dual rowClass="mt-4"
                     :en="$L('Supervision:').$p('Supervision is the responsibility of the Christian Hope Center Syria / Head Office Director. Depending on the circumstances, the Project Director may delegate supervision to another individual.')"
                     :ar="$L($Glyphs('الإشراف:')).$p($Glyphs('هو مسؤولية مدير مركز الرجاء المسيحي في سوريا، وحسب الظروف قد يفوّض مدير المشروع عملية الإشراف لشخص آخر.'))"
    />
</div>

<div class="avoid-break mt-4" style="line-height: 0.7">
    <x-contract.dual rowClass="mt-6"
                     :en="$L('Annual Leave:')"
                     :ar="$L($Glyphs('إجازات العمل التطوعي:'))"
    />
    <x-contract.dual rowClass="mt-6"
                     :en="$p('The volunteer is entitled to 12 days of working days leave during the validity period of the contract, with it being proportional to the duration of the contract.')"
                     :ar="$p($Glyphs(__('general.text27')))"
    />
    <x-contract.dual rowClass="mt-8"
                     :en="$p('The volunteer is entitled to 24 days of sickness leave in cases of operations and infectious diseases: 12 days paid and 12 days paid at half the value of the monthly stipend, provided that a medical report is submitted to justify the leave.')"
                     :ar="$p($Glyphs(__('general.text28')))"
    />
    <x-contract.dual rowClass="mt-6"
                     :en="$p('In case the volunteer consumes all annual and sickness leave days, he/she will be given twelve days of leave with the deduction of the number of leave days amount from monthly stipend.')"
                     :ar="$p($Glyphs(__('general.text29')))"
    />
</div>

<div class="avoid-break mt-6" style="line-height: 0.8">
    <x-contract.dual
        :en="$L('Internal Rules and Regulations:')"
        :ar="$L($Glyphs('القوانين والأنظمة الداخلية:'))"
    />
    <x-contract.dual
        :en="$p('Volunteer acknowledges and pledges to commit to the following attached documents:')"
        :ar="$p($Glyphs(__('general.text30')))"
    />
    @php
        $EN_LIST = ['Job description.', 'Code of Conduct.', 'Christian Hope Center Policies.'];
        $AR_LIST = [$Glyphs('توصيف العمل التطوعي.'), $Glyphs('القواعد الأخلاقية للتطوع.'), $Glyphs('سياسات مركز الرجاء المسيحي.')];
    @endphp
    <x-contract.dual rowClass="mt-2 "
                     :en="collect($EN_LIST)->map(fn($i) => '-    '.$i)->implode('<br>')"
                     :ar="collect($AR_LIST)->map(fn($i) => $i.$Glyphs('  -'))->implode('<br>')"
    />
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-6"
                     :en="$L('Expiration of the agreement:').$p('This agreement shall be terminated immediately in the event of:')"
                     :ar="$L($Glyphs('انتهاء العقد:')).$p($Glyphs('يعتبر هذا العقد منتهياً في الحالات التالية:'))"
    />
    @php
        $EN_END = ['Closure of the Christian Hope Center in Syria.'];
        $AR_END = [$Glyphs('إغلاق مركز الرجاء في سوريا.')];
    @endphp
    <x-contract.dual rowClass="mt-2"
                     :en="collect($EN_END)->map(fn($i) => '- '.$i)->implode('<br>')"
                     :ar="collect($AR_END)->map(fn($i) => $i.$Glyphs('  -'))->implode('<br>')"
    />
</div>
 --}}

 @php
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;
    $Glyphs = fn($s) => $forPdf ? AR::glyphs((string) $s) : (string) $s;
    $SmartGlyphs = fn($s, $limit = 2000) => $forPdf ? AR::smartGlyphs((string)$s, $limit) : (string)$s;
    $LB     = fn($t) => '<span class="h-label-bold">' . $t . '</span>';

    $asList = function(array $lines) {
        return '<div style="margin:0; padding:0;">'
            . implode('', array_map(fn($l) => '<div style="margin-top:2px;">'.$l.'</div>', $lines))
            . '</div>';
    };

    $arBullet = function(array $lines) use ($SmartGlyphs) {
        $html = '';
        $count = count($lines);
        foreach ($lines as $i => $line) {
            $processed = $SmartGlyphs($line);
            if ($i === 0) {
                $html .= $processed . ' -';
            } else {
                $html .= $processed;
            }
            if ($i < $count - 1) {
                $html .= '<br>';
            }
        }
        return $html;
    };
@endphp

<div style="line-height: 1.15;">
    <div class="avoid-break" style="margin-top: 10px;">
        <x-contract.dual 
            :en="$LB('10. Internal Rules and Regulations:')" 
            :ar="$LB($Glyphs('القوانين والأنظمة الداخلية:') . ' .10')" />
        
        <x-contract.dual class="mt-1"
            :en="'The second party acknowledges and pledges to commit to the following attached documents:'"
            :ar="$SmartGlyphs('يُقر الفريق الثاني ويتعهد بالاطلاع والالتزام<br>بالمستندات المرفقة التالية:')" />

        @php
            $EN_RULES = ['- Job description.', '- Code of Conduct.', '- Christian Hope Center Policies.'];
            $AR_RULES = [
                $SmartGlyphs('توصيف العمل التطوّعي.') . ' -', 
                $SmartGlyphs('القواعد الأخلاقية للتطوّع.') . ' -', 
                $SmartGlyphs('سياسات مؤسسة مركز الرجاء المسيحي.') . ' -'
            ];
        @endphp
        <x-contract.dual class="mt-1" :en="$asList($EN_RULES)" :ar="$asList($AR_RULES)" />
    </div>

    <div class="avoid-break" style="margin-top: 15px;">
        <x-contract.dual 
            :en="$LB('11. Expiration of the Contract:')" 
            :ar="$LB($Glyphs('انتهاء العقد:') . ' .11')" />
        
        <x-contract.dual class="mt-1"
            :en="'This contract is considered to be terminated in case of:'"
            :ar="$SmartGlyphs('يعتبر هذا العقد منتهياً في الحالات التالية:')" />

        @php
            $EN_TERMINATION = [
                '- The closure of the Christian Hope Center in Syria.',
                '- Project expiration.',
                '- Expiration of the agreement.',
                '- Breaching of this agreement and the attached job description by the second team after notice and warning.',
                '- Breaching the Code of Conduct after notice and warning.',
                '- Breaching the Christian Hope Center policies and bylaws after notice and warning.',
                '- Unsatisfactory results of job performance evaluation.'
            ];
            
            $AR_TERMINATION = [
                $arBullet(['إغلاق مؤسسة مركز الرجاء المسيحي في سوريا.']),
                $arBullet(['انتهاء المشروع.']),
                $arBullet(['انتهاء مدة العقد.']),
                $arBullet([
                    'في حال عدم التقيّد ببنود هذا العقد وتوصيف العمل',
                    'التطوّعي المرفق من قبل الفريق الثاني بعد التنبيه والانذار.'
                ]),
                $arBullet([
                    'في حال المخالفة لمدونة القواعد الأخلاقية للتطوّع',
                    'بعد التنبيه والانذار.'
                ]),
                $arBullet([
                    'في حال مخالفة سياسات مؤسسة مركز الرجاء المسيحي',
                    'بعد التنبيه والانذار.'
                ]),
                $arBullet(['نتائج تقييم الأداء غير المُرضيّة.'])
            ];
        @endphp
        <x-contract.dual class="mt-1" :en="$asList($EN_TERMINATION)" :ar="$asList($AR_TERMINATION)" />
    </div>

    <div class="avoid-break" style="margin-top: 15px;">
        <x-contract.dual 
            :en="$LB('12. Termination of the Contract:')" 
            :ar="$LB($Glyphs('إنهاء العقد:') . ' .12')" />
        
        <x-contract.dual class="mt-1"
            :en="'Hope Center Aleppo / Head office has the right to terminate this contract during the effective period, by informing the volunteer through a written termination notice one month in advance.'"
            :ar="
                $SmartGlyphs('يحق للفريق الأول إنهاء هذا العقد في أي وقت قبل انتهاء مدته') . '<br>' .
                $SmartGlyphs('وذلك بإعلام الفريق الثاني خطيّاً قبل شهر.')
            " />
    </div>

    <div class="avoid-break" style="margin-top: 15px;">
        <x-contract.dual 
            :en="$LB('13. Duties and obligations on the extension, expiration, or termination of the agreement:')" 
            :ar="$LB($Glyphs('الواجبات والالتزامات على تمديد أو انتهاء أو إنهاء العقد:') . ' .13')" />
        
        @php
            $EN_OBLIGATIONS = [
                '- The first party is required to notify the second party through a written notice of the termination or extension date one month in advance.',
                '- The second party is required to notify the first party of their wish to end the volunteering one month in advance.',
                '- The second party is committed to hand over to Christian Hope Center all the equipment, material, and documents that they received upon the termination or expiration of this contract, based on the personal receipt list of the volunteer.'
            ];
            
            $AR_OBLIGATIONS = [
                $arBullet([
                    'يلتزم الفريق الأول عند رغبته بالتمديد أو عدمه بأن',
                    'يُبلّغ الفريق الثاني بذلك بشكل خطيّ قبل مدة شهر.'
                ]),
                $arBullet([
                    'يلتزم الفريق الثاني عند رغبته بمغادرة العمل التطوّعي',
                    'خلال مدة سريان العقد بأن يبلغ الفريق الأول بذلك',
                    'بشكل خطيّ قبل مدة شهر.'
                ]),
                $arBullet([
                    'يلتزم الفريق الثاني عند انتهاء أو إنهاء فترة تطوّعه',
                    'بتسليم جميع التجهيزات والمواد والوثائق المؤتمن بها',
                    'للفريق الأول وذلك استناداً لقائمة الاستلام الشخصي.'
                ])
            ];
        @endphp
        <x-contract.dual class="mt-1" :en="$asList($EN_OBLIGATIONS)" :ar="$asList($AR_OBLIGATIONS)" />
    </div>
</div>
