@php
    use App\Support\ArabicText as AR;
    $forPdf = $forPdf ?? false;
    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $LB     = fn ($t) => '<span class="h-label-bold">' . $t . '</span>';
        $asList = function(array $lines) {
        return '<div class="list-plain">'
            . implode('', array_map(fn($l) => '<div>'.$l.'</div>', $lines))
            . '</div>';
    };
@endphp

<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('9. Annual Leave:')" :ar="$LB($Glyphs('9. إجازات العمل التطوعّي:'))" />
    
    <x-contract.dual class="mt-1"
        :en="'- The second party is not entitled to any leave days during the period of the trial contract.'" 
        :ar="$Glyphs('- لا يحق للفريق الثاني إجازات  خلال فترة سريان العقد التجريبي.')" />


    <x-contract.dual class="mt-1"
        :en="'- If the second party has a strong reason to be absent, they must inform the first party, as the leave shall not exceed 3 days in a row during the contract period, except in cases of death or severe sickness.'" 
        :ar="$Glyphs('- في حال اضطرّار الفريق الثاني للغياب فعليه أخذ موافقة الفريق الأول بذلك على ألا يتجاوز الأمر أكثر من 3 أيام متتالية خلال فترة العقد، وذلك ما عدا حالات الوفاة والمرض.')" />

    <x-contract.dual class="mt-1"
        :en="'- The second party stipend amount will be deducted for one working day in case of repeated delays beyond the specified working hours more than 3 times a month.'" 
        :ar="$Glyphs('- يُخصم يوم عمل تطوّّعي للفريق الثاني من مكافأة التطوّّع المخصصة له في حال تكرار التأخير عن موعد الدوام المحدد لأكثر من 3 مرات في الشهر.')" />
</div>

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

<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('11. Termination of the Contract:')" :ar="$LB($Glyphs('11. إنهاء العقد:'))" />
    @php
        $EN_TERM = [
            '- The first party has the right to terminate this contract at any time before its expiry date in case the second party violates the terms of this contract and the volunteering code of conduct.',
            '- If the second party wishes to terminate the contract, the second party is obliged to inform the first party at least a Two week before the end of the contract period.',
            '- The second party is committed to hand over to "Christian Hope Center" all the equipment, material, and documents that they received upon the termination or expiration of this contract, based on the personal receipt list of the volunteer.'
        ];
        $AR_TERM = [
            $Glyphs('- يحق للفريق الأول إنهاء هذا العقد في أي وقت قبل إنتهاء مدته في حال قيام الفريق الثاني بمخالفة بنود هذا العقد والمستندات المرفقة به.'),
            $Glyphs('- في حال رغبة الفريق الثاني بفسخ العقد، يلتزم بأن يُعلم الفريق الأول قبل أسبوعين على الأقل من تاريخ انتهاء العقد.'),
            $Glyphs('- يلتزم الفريق الثاني عند انتهاء أو إنهاء العقد بتسليم جميع التجهيزات والمواد والوثائق المؤتمن بها للفريق الأول وذلك استناداً لقائمة الاستلام الشخصي.')
        ];
    @endphp
    <x-contract.dual class="mt-1" :en="$asList($EN_TERM)" :ar="$asList($AR_TERM)" />
</div>

<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('12. Special conditions:')" :ar="$LB($Glyphs('12. شروط خاصة:'))" />
    <x-contract.dual class="mt-1"
        :en="'The second team commits to maintaining the confidentiality of all information and documents they have access to, and not using them outside the scope of the volunteer work. The first team has the right to take legal action against the second team in case of a breach of this clause.'"
        :ar="$Glyphs('يتعهد الفريق الثاني بالحفاظ على سريّة جميع المعلومات والوثائق التي يطّلّع عليها، وعدم استخدامها خارج نطاق العمل التطوّعي. ويحق للفريق الأول مقاضاة الفريق الثاني في حال إخلاله بهذا البند.')" />
</div>
