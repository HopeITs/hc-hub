@php
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $LB     = fn ($t) => '<span class="h-label-bold">' . $t . '</span>';

    $asList = function(array $lines) {
        return '<div style="margin:0; padding:0;">'
            . implode('', array_map(fn($l) => '<div style="margin-top:2px;">'.$l.'</div>', $lines))
            . '</div>';
    };

    $esc   = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));
    $escAr = fn ($v) => e((string)($v === '' || $v === null ? '—' : $Glyphs($v)));
@endphp


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


<div class="avoid-break mt-4">
    <x-contract.dual 
        :en="$LB('14. Special conditions:')" 
        :ar="$LB($Glyphs('14. شروط خاصة:'))" />
    <x-contract.dual class="mt-1"
        :en="'The second team commits to maintaining the confidentiality of all information and documents they have access to, and not using them outside the scope of the volunteer work. The first team has the right to take legal action against the second team in case of a breach of this clause.'"
        :ar="$Glyphs('يتعهد الفريق الثاني بالحفاظ على سريّة جميع المعلومات والوثائق التي يطّلّع عليها، وعدم استخدامها خارج نطاق العمل التطوّعي. ويحق للفريق الأول مقاضاة الفريق الثاني في حال إخلاله بهذا البند.')" />
</div>

