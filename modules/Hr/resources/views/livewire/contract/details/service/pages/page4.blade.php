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
        :en="$LB('12. This contract') . ' is a voluntary contract and is <strong>not subject to Labor Law No. /17/ of 2010</strong> or any amendments to the law or any new law, and it is also <strong>not subject to social security Law No. 92 of 1959 and its amendments</strong>. The second team has no right to claim any end-of-service compensation upon the expiration or termination of the second party contract.'"
        :ar="$LB($Glyphs('12. إن هذا العقد')) . $Glyphs(' هو عقد تطوّعي ') . '<strong>' . $Glyphs('لا يخضع لقانون العمل رقم /17/ لعام 2010') . '</strong>' . $Glyphs(' أو أي تعديل للقانون أو أي قانون جديد، وكذلك ') . '<strong>' . $Glyphs('لا يخضع لقانون التأمينات الاجتماعية رقم 92 لعام 1959 وتعديلاته') . '</strong>' . $Glyphs('، ولا يحق للفريق الثاني المطالبة بأي تعويض نهاية خدمة عند انتهاء عقد التطوّع أو إنهائه.')" />
</div>
<div class="avoid-break mt-4">
    <x-contract.dual 
        :en="$LB('13. The contract is issued') . ' in Arabic and translated into English.'"
        :ar="$LB($Glyphs('13. صدر هذا العقد')) . $Glyphs(' باللغة العربية وتُرجم إلى اللغة الإنكليزية.')" />
</div>

<div class="avoid-break mt-4">
    <x-contract.dual 
        :en="$LB('14. This contract was issued') . ' on 4 pages and contains 14 clauses, printed in a single copy preserved at the Head Office in Aleppo.'"
        :ar="$LB($Glyphs('14. صدر هذا العقد')) . $Glyphs(' على 4 صفحات وعلى أربعة عشر بنداً، مطبوعاً على نسخة واحدة محفوظة لدى الإدارة العامة في حلب.')" />
</div>

<div class="avoid-break mt-6">
    <x-contract.dual
        :en="'I hereby certify having read and understood the conditions stipulated above and all the attached documents and confirm that I accept them all.'"
        :ar="$Glyphs('أفيد بأنني قرأت وفهمت شروط العمل المنصوص عليها أعلاه وكافة المستندات المرفقة وأقر بموافقتي على كل ما ورد، وعليه أوقّع.')" />
</div>

<div class="avoid-break contract-signature">

    <div class="signature-party signature-party-second">
        <div class="signature-role">
            {!! $LB('Second Party') !!} / {!! $LB($Glyphs('الفريق الثاني')) !!}
        </div>
        <div class="signature-caption">
            Name and Signature / {{ $Glyphs('الاسم والتوقيع') }}
        </div>
        <div class="signature-name">
            <strong>{{ $esc($nameEn) }}</strong>
            <br>
            <strong>{{ $escAr($nameAr) }}</strong>
        </div>
        <div class="signature-date">
            Date / {{ $Glyphs('التاريخ') }}
        </div>
    </div>

    <div class="signature-party signature-party-first">
        <div class="signature-role">
            {!! $LB('First Party') !!} / {!! $LB($Glyphs('الفريق الأول')) !!}
        </div>
        <div class="signature-caption">
            Name and Signature / {{ $Glyphs('الاسم والتوقيع') }}
        </div>
        <div class="signature-name">
            <strong>Fredy Georges Youssef</strong>
            <br>
            <strong>{{ $Glyphs('فريدي جرجس يوسف') }}</strong>
        </div>
        <div class="signature-date">
            Date / {{ $Glyphs('التاريخ') }}
        </div>
    </div>

</div>