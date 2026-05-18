@php
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;
    $Glyphs = fn($s) => $forPdf ? AR::glyphs((string) $s) : (string) $s;
    $LB     = fn($t) => '<span class="h-label-bold">' . $t . '</span>';

    $asList = function(array $lines) {
        return '<div style="margin:0; padding:0;">'
            . implode('', array_map(fn($l) => '<div style="margin-top:2px;">'.$l.'</div>', $lines))
            . '</div>';
    };
@endphp

<div class="avoid-break mt-1">
    <x-contract.dual 
        :en="$LB('10. Internal Rules and Regulations:')" 
        :ar="$LB($Glyphs('10. القوانين والأنظمة الداخلية:'))" />
    
    <x-contract.dual class="mt-1"
        :en="'The second party acknowledges and pledges to commit to the following attached documents:'"
        :ar="$Glyphs('الفريق الثاني يُقر ويتعهد بالاطلاع والالتزام بالمستندات المرفقة التالية:')" />

    @php
        $EN_RULES = ['- Job description.', '- Code of Conduct.', '- Christian Hope Center Policies.'];
        $AR_RULES = [$Glyphs('- توصيف العمل التطوّعي.'), $Glyphs('- القواعد الأخلاقية للتطوّع.'), $Glyphs('- سياسات مؤسسة مركز الرجاء المسيحي.')];
    @endphp
    <x-contract.dual class="mt-1" 
        :en="$asList($EN_RULES)" 
        :ar="$asList($AR_RULES)" />
</div>

<div class="avoid-break mt-1">
    <x-contract.dual 
        :en="$LB('11. Expiration of the Contract:')" 
        :ar="$LB($Glyphs('11. انتهاء العقد:'))" />
    
    <x-contract.dual class="mt-1"
        :en="'This contract is considered to be terminated in case of:'"
        :ar="$Glyphs('يعتبر هذا العقد منتهياً في الحالات التالية:')" />

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
            $Glyphs('- إغلاق مؤسسة مركز الرجاء المسيحي في سوريا.'),
            $Glyphs('- انتهاء المشروع.'),
            $Glyphs('- انتهاء مدة العقد.'),
            $Glyphs('- في حال عدم التقيّد ببنود هذا العقد وتوصيف العمل التطوّعي المرفق من قبل الفريق الثاني بعد التنبيه والانذار.'),
            $Glyphs('- في حال المخالفة لمدونة القواعد الأخلاقية للتطوّع بعد التنبيه والانذار.'),
            $Glyphs('- في حال مخالفة سياسات مؤسسة مركز الرجاء المسيحي بعد التنبيه والانذار.'),
            $Glyphs('- نتائج تقييم الأداء غير المُرضيّة.')
        ];
    @endphp
    <x-contract.dual class="mt-1" 
        :en="$asList($EN_TERMINATION)" 
        :ar="$asList($AR_TERMINATION)" />
</div>

<div class="avoid-break mt-1">
    <x-contract.dual 
        :en="$LB('12. Termination of the Contract:')" 
        :ar="$LB($Glyphs('12. إنهاء العقد:'))" />
    
    <x-contract.dual class="mt-1"
        :en="'Hope Center Aleppo / Head office has the right to terminate this contract during the effective period, by informing the volunteer through a written termination notice one month in advance.'"
        :ar="$Glyphs('يحق للفريق الأول إنهاء هذا العقد في أي وقت قبل انتهاء مدته وذلك بإعلام الفريق الثاني خطيّاً قبل شهر.')" />
</div>

<div class="avoid-break mt-1">
    <x-contract.dual 
        :en="$LB('13. Duties and obligations on the extension, expiration, or termination of the agreement:')" 
        :ar="$LB($Glyphs('13. الواجبات والالتزامات على تمديد أو انتهاء أو إنهاء العقد:'))" />
    
    @php
        $EN_OBLIGATIONS = [
            '- The first party is required to notify the second party through a written notice of the termination or extension date one month in advance.',
            '- The second party is required to notify the first party of their wish to end the volunteering one month in advance.',
            '- The second party is committed to hand over to Christian Hope Center all the equipment, material, and documents that they received upon the termination or expiration of this contract, based on the personal receipt list of the volunteer.'
        ];
        $AR_OBLIGATIONS = [
            $Glyphs('- يلتزم الفريق الأول عند رغبته بالتمديد أو عدمه بأن يُبلّغ الفريق الثاني بذلك بشكل خطيّ قبل مدة شهر.'),
            $Glyphs('- يلتزم الفريق الثاني عند رغبته بمغادرة العمل التطوّعي خلال مدة سريان العقد بأن يبلغ الفريق الأول بذلك بشكل خطيّ قبل مدة شهر.'),
            $Glyphs('- يلتزم الفريق الثاني عند انتهاء أو إنهاء فترة تطوّعه بتسليم جميع التجهيزات والمواد والوثائق المؤتمن بها للفريق الأول وذلك استناداً لقائمة الاستلام الشخصي.')
        ];
    @endphp
    <x-contract.dual class="mt-1" 
        :en="$asList($EN_OBLIGATIONS)" 
        :ar="$asList($AR_OBLIGATIONS)" />
</div>