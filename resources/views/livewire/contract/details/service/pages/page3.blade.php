@php
    use App\Support\ArabicText as AR;
    $forPdf = $forPdf ?? false;
    $Glyphs = fn($s) => $forPdf ? AR::glyphs((string) $s) : (string) $s;
    $LB = fn($t) => '<span class="h-label-bold">' . $t . '</span>';
    $asList = function (array $lines) {
        return '<div style="margin:0; padding:0;">' .
            implode('', array_map(fn($l) => '<div style="margin-top:2px;">' . $l . '</div>', $lines)) .
            '</div>';
    };
@endphp


<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('10. Termination of the Contract:')" :ar="$LB($Glyphs('10. إنهاء العقد:'))" />
    @php
$EN_TERM = [
            '- The first party has the right to terminate this contract at any time before its expiry date in case the second party violates the terms of this contract and the volunteering code of conduct, or in the case of Unsatisfactory results of job performance.',
            '- This contract will not be renewed without a written agreement of both parties, whereas each party shall inform the other of the contract termination one month prior to the end of the contract.',
            '- The second party is committed to hand over to “Christian Hope Center” all the equipment, material, and documents that they received upon the termination or expiration of this contract, based on the personal receipt list of the volunteer.',
        ];
        $AR_TERM = [
            $Glyphs('- يحق للفريق الأول إنهاء هذا العقد في أي وقت قبل انتهاء مدته في حال قيام الفريق الثاني بمخالفة بنود هذا العقد والمستندات المرفقة به أو في حال نتائج تقييم الأداء غير الجيدة.'),
            $Glyphs('- لا يتجدد هذا العقد لمدة أخرى إلا بموافقة الفريقين خطياً، على أن يعلم كل فريق الآخر بعدم التجديد قبل شهر من انتهاء العقد، أو قبل شهر من تاريخ الرغبة بفسخ العقد.'),
            $Glyphs('- يلتزم الفريق الثاني عند انتهاء أو إنهاء العقد بتسليم جميع التجهيزات والمواد والوثائق المؤتمن بها للفريق الأول وذلك استناداً لقائمة الاستلام الشخصي.'),
        ];
    @endphp
    <x-contract.dual class="mt-1" :en="$asList($EN_TERM)" :ar="$asList($AR_TERM)" />
</div>

<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('11. Special conditions:')" :ar="$LB($Glyphs('11. شروط خاصة:'))" />

    @php
        $EN_SPECIAL = [
            '- If the second party has a strong purpose to be absent, they must inform the first party.',
            '- The second party stipend amount will be deducted for one working day in case of repeated delays beyond the specified working hours more than 3 times a month.',
            '- The second team commits to maintaining the confidentiality of all information and documents they have access to, and not using them outside the scope of the volunteer work. The first team has the right to take legal action against the second team in case of a breach of this clause.',
        ];

        $AR_SPECIAL = [
            $Glyphs('- في حال اضطرار الفريق الثاني للغياب فعليه أخذ موافقة الفريق الأول بذلك.'),
            $Glyphs(
                '- يُخصم يوم عمل تطوعي للفريق الثاني من مكافأة التطوع المخصصة له في حال تكرار التأخير عن مواعيد الدوام المحددة لأكثر من 3 مرات في الشهر مبلغاً يعادل قيمة يوم دوام / جلسة.',
            ),
            $Glyphs(
                '- يتعهد الفريق الثاني بالحفاظ على سرية جميع المعلومات والوثائق التي يطلع عليها، وعدم استخدامها خارج نطاق العمل التطوعي. ويحق للفريق الأول مقاضاة الفريق الثاني في حال إخلاله بهذا البند.',
            ),
        ];
    @endphp

    <x-contract.dual class="mt-1" :en="$asList($EN_SPECIAL)" :ar="$asList($AR_SPECIAL)" />
</div>
