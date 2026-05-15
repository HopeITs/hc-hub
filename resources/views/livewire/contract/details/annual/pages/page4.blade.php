@php
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $LB     = fn ($t) => '<span class="h-label-bold">' . $t . '</span>';

    $emp    = $contract->employee;

    $nameEn = trim(($emp?->first_name_en ?? '') . ' ' . ($emp?->father_name_en ?? '') . ' ' . ($emp?->last_name_en ?? ''));
    $nameEn = $nameEn !== '' ? $nameEn : '—';


    $nameAr = trim(($emp?->first_name_ar ?? '') . ' ' . ($emp?->father_name_ar ?? '') . ' ' . ($emp?->last_name_ar ?? ''));
    $nameAr = $nameAr !== '' ? $nameAr : '—';

    $esc   = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));
    $escAr = fn ($v) => e((string)($v === '' || $v === null ? '—' : $Glyphs($v)));
@endphp

<div class="avoid-break mt-4">
    <x-contract.dual 
        :en="$LB('14. Special conditions:')" 
        :ar="$LB($Glyphs('14. شروط خاصة:'))" />
    <x-contract.dual class="mt-1"
        :en="'The second team commits to maintaining the confidentiality of all information and documents they have access to, and not using them outside the scope of the volunteer work. The first team has the right to take legal action against the second team in case of a breach of this clause.'"
        :ar="$Glyphs('يتعهد الفريق الثاني بالحفاظ على سريّة جميع المعلومات والوثائق التي يطّلّع عليها، وعدم استخدامها خارج نطاق العمل التطوّعي. ويحق للفريق الأول مقاضاة الفريق الثاني في حال إخلاله بهذا البند.')" />
</div>

<div class="avoid-break mt-4">
    <x-contract.dual 
        :en="$LB('15. This contract') . ' is a voluntary contract and is <strong>not subject to Labor Law No. /17/ of 2010</strong> or any amendments to the law or any new law, and it is also <strong>not subject to social security Law No. 92 of 1959 and its amendments</strong>. The second team has no right to claim any end-of-service compensation upon the expiration or termination of the second party contract.'"
        :ar="$LB($Glyphs('15. إن هذا العقد')) . $Glyphs(' هو عقد تطوّعي ') . '<strong>' . $Glyphs('لا يخضع لقانون العمل رقم /17/ لعام 2010') . '</strong>' . $Glyphs(' أو أي تعديل للقانون أو أي قانون جديد، وكذلك ') . '<strong>' . $Glyphs('لا يخضع لقانون التأمينات الاجتماعية رقم 92 لعام 1959 وتعديلاته') . '</strong>' . $Glyphs('، ولا يحق للفريق الثاني المطالبة بأي تعويض نهاية خدمة عند انتهاء عقد التطوّع أو إنهائه.')" />
</div>
<div class="avoid-break mt-4">
    <x-contract.dual 
        :en="$LB('16. The contract is issued') . ' in Arabic and translated into English.'"
        :ar="$LB($Glyphs('16. صدر هذا العقد')) . $Glyphs(' باللغة العربية وتُرجم إلى اللغة الإنكليزية.')" />
</div>

<div class="avoid-break mt-4">
    <x-contract.dual 
        :en="$LB('17. This contract was issued') . ' on 4 pages and contains 17 clauses, printed in a single copy preserved at the Head Office in Aleppo.'"
        :ar="$LB($Glyphs('17. صدر هذا العقد')) . $Glyphs(' على 4 صفحات وعلى سبعة عشر بنداً، مطبوعاً على نسخة واحدة محفوظة لدى الإدارة العامة في حلب.')" />
</div>

<div class="avoid-break mt-6">
    <x-contract.dual
        :en="'I hereby certify having read and understood the conditions stipulated above and all the attached documents and confirm that I accept them all.'"
        :ar="$Glyphs('أفيد بأنني قرأت وفهمت شروط العمل المنصوص عليها أعلاه وكافة المستندات المرفقة وأقر بموافقتي على كل ما ورد، وعليه أوقّع.')" />
</div>

<div class="avoid-break" style="position: relative; margin-top: 45px; min-height: 220px;">
    
    <div style="position: absolute; left: 0; text-align: center; width: 48%;">
        <div style="margin-bottom: 12px;">
            {!! $LB('Second Party') !!} / {!! $LB($Glyphs('الفريق الثاني')) !!}
        </div>
        <div style="margin-bottom: 15px;">
            Name and Signature / {{ $Glyphs('الاسم والتوقيع') }}
        </div>
        <div style="line-height: 1.6; margin-bottom: 25px;">
            <strong>{{ $esc($nameEn) }}</strong>
            <br>
            <strong>{{ $escAr($nameAr) }}</strong>
        </div>
        <div style="font-size: 14px;">
            Date / {{ $Glyphs('التاريخ') }}
        </div>
    </div>

    <div style="position: absolute; right: 0; text-align: center; width: 48%;">
        <div style="margin-bottom: 12px;">
            {!! $LB('First Party') !!} / {!! $LB($Glyphs('الفريق الأول')) !!}
        </div>
        <div style="margin-bottom: 15px;">
            Name and Signature / {{ $Glyphs('الاسم والتوقيع') }}
        </div>
        <div style="line-height: 1.6; margin-bottom: 25px;">
            <strong>Fredy Georges Youssef</strong>
            <br>
            <strong>{{ $Glyphs('فريدي جرجس يوسف') }}</strong>
        </div>
        <div style="font-size: 14px;">
            Date / {{ $Glyphs('التاريخ') }}
        </div>
    </div>

</div>