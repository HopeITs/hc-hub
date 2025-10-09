@php
    use App\Support\ArabicText as AR;

    $forPdf  = $forPdf ?? false;
    $Glyphs  = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $Digits  = fn ($s) => $forPdf ? ar_digits((string)$s) : (string)$s;
    $L       = fn (string $t) => '<span class="h-label">'.$t.'</span>';

    // Salary
    $salary  = (float) ($contract->salary ?? 0);
    $salaryFormattedEn = $salary ? number_format($salary, 0, '.', ',') : '—';
    $salaryFormattedAr = $salary ? $Digits(number_format($salary, 0, '.', ',')) : '—';

    // Currency mapping (IDs or codes)
    $CURRENCY = [
        1     => ['code' => 'SYP', 'en' => 'Syrian Pounds', 'ar' => 'ليرة سورية'],
        2     => ['code' => 'USD', 'en' => 'US Dollars',    'ar' => 'دولار أمريكي'],
        3     => ['code' => 'EUR', 'en' => 'European Euro', 'ar' => 'يورو أوروبي'],
        'SYP' => ['code' => 'SYP', 'en' => 'Syrian Pounds', 'ar' => 'ليرة سورية'],
        'USD' => ['code' => 'USD', 'en' => 'US Dollars',    'ar' => 'دولار أمريكي'],
        'EUR' => ['code' => 'EUR', 'en' => 'European Euro', 'ar' => 'يورو أوروبي'],
    ];

    $currencyKey = $contract->currency ?? 1; // could be ID or code
    $cur = $CURRENCY[$currencyKey] ?? ['code' => 'SYP', 'en' => 'Syrian Pounds', 'ar' => 'ليرة سورية'];
@endphp


{{-- Third term continuation (6–7) --}}
<div class="avoid-break mt-4">
    <x-contract.dual :en="$L('Third term:')" :ar="$L($Glyphs('البند الثالث:'))"/>
</div>

{{-- 6 --}}
<x-contract.dual rowClass="mt-6 mb-10"
                 :en="'6. The first party is committed to paying the second party in exchange for his/her services, a monthly stipend of '.$salaryFormattedEn.' '.$cur['code'].' / '.$cur['en'].' only.'"
                 :ar="'6. يلتزم الفريق الأول بدفع مكافأة شهرية للفريق الثاني لقاء خدماته مقدارها '.$salaryFormattedAr.' '.$cur['code'].' / '.$cur['ar'].' فقط لا غير.'"
/>

{{-- 7 --}}
<x-contract.dual rowClass="mt-6 mb-10"
                 :en="'7. The second party has to commit, abide and sign the code of conduct.'"
                 :ar="'7. على الفريق الثاني الامتثال للقواعد الأخلاقية للتطوع والتقيد بها وتوقيعها.'"
/>

{{-- Fourth term --}}
<div class="avoid-break mt-6">
    <x-contract.dual :en="$L('Fourth term:')" :ar="$L($Glyphs('البند الرابع:'))"/>
</div>

{{-- 1 --}}
<x-contract.dual rowClass="mt-6 mb-10"
                 :en="'1. The second party stipend amount will be deducted an amount of one working day in case of repeated delays beyond the specified working hours for more than 3 times a month.'"
                 :ar="'1. يخصم يوم عمل تطوعي للفريق الثاني من المكافأة المالية المخصصة له في حال تكرار التأخير عن موعد الدوام المحدد لأكثر من 3 مرات في الشهر.'"
/>

{{-- 2 --}}
<x-contract.dual rowClass="mt-6 mb-10"
                 :en="'2. The volunteer is not entitled to any leave days during the period of the trial contract.'"
                 :ar="'2. لا يحق للمتطوع إجازات خلال فترة سريان العقد التجريبي.'"
/>

{{-- 3 --}}
<x-contract.dual rowClass="mt-6 mb-10"
                 :en="'3. If the second party has a strong reason to be absent, he/she must inform the first party, without exceeding 3 days in a row during the contract period with the exception of cases regarding death or severe sickness.'"
                 :ar="'3. في حال اضطرار الفريق الثاني للغياب فعليه أخذ موافقة الفريق الأول بذلك على أن لا يتجاوز الأمر أكثر من 3 أيام متتالية خلال فترة العقد، وذلك ما عدا حالات الوفاة أو المرض.'"
/>

{{-- 4 --}}
<x-contract.dual rowClass="mt-6 mb-10"
                 :en="'4. The first party has the right to terminate this contract at any time before its expiry date in case the second party violates the terms of this contract and the volunteering code of conduct, in which case all due amounts must be paid to the second party up to the termination date.'"
                 :ar="'4. يحق للفريق الأول إنهاء هذا العقد في أي وقت قبل انتهاء مدته في حال قيام الطرف الثاني بمخالفة بنود هذا العقد والقواعد الأخلاقية للتطوع، وعليه في هذه الحالة يجب أن يدفع للفريق الثاني كافة المبالغ المستحقة له حتى تاريخ الإنهاء'"
/>
