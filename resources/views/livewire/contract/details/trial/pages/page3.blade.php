@php
    use Illuminate\Support\Number;

    $amount     = $contract->salary ?? null; // numeric
    $currencyId = (int) ($contract->currency ?? 0); // 1 = SYP, 2 = USD (fallback unknown)

    $CURRENCY = [
        1 => ['code' => 'SYP', 'en' => 'Syrian Pounds', 'ar' => 'ليرة سورية'],
        2 => ['code' => 'USD', 'en' => 'US Dollars',    'ar' => 'دولار أمريكي'],
    ];
    $cur   = $CURRENCY[$currencyId] ?? ['code' => 'SYP', 'en' => 'Syrian Pounds', 'ar' => 'ليرة سورية'];

    $amtEn = is_numeric($amount) ? Number::format($amount, locale: 'en') : '—';
    $amtAr = is_numeric($amount) ? Number::format($amount, locale: 'ar') : '—';

    $EN_THIRD_TERM_LABEL  = '<span class="h-label">Third term:</span>';
    $AR_THIRD_TERM_LABEL  = '<span class="h-label">البند الثالث:</span>';

    $EN_FOURTH_TERM_LABEL = '<span class="h-label">Fourth term:</span>';
    $AR_FOURTH_TERM_LABEL = '<span class="h-label">البند الرابع:</span>';

    $EN_LI_6 = "The first party is committed to paying the second party in exchange for his/her services, a monthly stipend of {$amtEn} {$cur['code']} / {$cur['en']} only.";
    $AR_LI_6 = "يلتزم الفريق الأول بدفع مكافأة شهرية للفريق الثاني لقاء خدماته مقدارها {$amtAr} {$cur['code']} / {$cur['ar']} فقط لا غير.";

    $EN_LI_7 = 'The second party has to commit, abide and sign the code of conduct.';
    $AR_LI_7 = 'على الفريق الثاني الالتزام والتقيد والتوقيع على قواعد السلوك الأخلاقي.';

    // ===== Fourth term: items 1–4 =====
    $EN_FOURTH_1 = 'The second party stipend amount will be deducted an amount of one working day in case of repeated delays beyond the specified working hours for more than 3 times a month.';
    $AR_FOURTH_1 = 'يُحسم من مكافأة الفريق الثاني مقدار يوم عمل في حال تكرار التأخر عن موعد الدوام المحدد أكثر من 3 مرات في الشهر.';

    $EN_FOURTH_2 = 'The volunteer is not entitled to any leave days during the period of the trial contract.';
    $AR_FOURTH_2 = 'لا يحق للمتطوع إجازات خلال فترة سريان العقد التجريبي.';

    $EN_FOURTH_3 = 'If the second party has a strong reason to be absent, he/she must inform the first party, without exceeding 3 days in a row during the contract period with the exception of cases regarding death or severe sickness.';
    $AR_FOURTH_3 = 'في حال اضطرار الفريق الثاني للغياب، فعليه إعلام الفريق الأول، على أن لا يتجاوز الأمر أكثر من 3 أيام متتالية خلال فترة العقد، وذلك ما عدا حالات الوفاة أو المرض الشديد.';

    $EN_FOURTH_4 = 'The first party has the right to terminate this contract at any time before its expiry date in case the second party violates the terms of this contract and the volunteering code of conduct, in which case all due amounts must be paid to the second party up to the termination date.';
    $AR_FOURTH_4 = 'يحق للفريق الأول إنهاء هذا العقد في أي وقت قبل تاريخ انتهائه في حال خالف الطرف الثاني بنود العقد ولوائح قواعد السلوك التطوعي، وعليه في هذه الحالة يجب أن تُدفع للفريق الثاني كافة المبالغ المستحقة له حتى تاريخ الإنهاء.';
@endphp

<x-contract.dual rowClass="mt-1" :en="$EN_THIRD_TERM_LABEL" :ar="$AR_THIRD_TERM_LABEL"/>

<div class="dual-row mt-2">
    <div class="dual-col dual-en">
        <ol class="list-decimal pl-5 space-y-3" start="6">
            <li>{!! $EN_LI_6 !!}</li>
            <li>{!! $EN_LI_7 !!}</li>
        </ol>
    </div>
    <div class="dual-col dual-ar">
        <ol class="list-decimal pr-5 space-y-3" start="6" style="direction: rtl; text-align: right;">
            <li>{!! $AR_LI_6 !!}</li>
            <li>{!! $AR_LI_7 !!}</li>
        </ol>
    </div>
</div>

<x-contract.dual rowClass="mt-5" :en="$EN_FOURTH_TERM_LABEL" :ar="$AR_FOURTH_TERM_LABEL"/>

<div class="dual-row mt-2">
    <div class="dual-col dual-en">
        <ol class="list-decimal pl-5 space-y-3">
            <li>{!! $EN_FOURTH_1 !!}</li>
            <li>{!! $EN_FOURTH_2 !!}</li>
            <li>{!! $EN_FOURTH_3 !!}</li>
            <li>{!! $EN_FOURTH_4 !!}</li>
        </ol>
    </div>
    <div class="dual-col dual-ar">
        <ol class="list-decimal pr-5 space-y-3" style="direction: rtl; text-align: right;">
            <li>{!! $AR_FOURTH_1 !!}</li>
            <li>{!! $AR_FOURTH_2 !!}</li>
            <li>{!! $AR_FOURTH_3 !!}</li>
            <li>{!! $AR_FOURTH_4 !!}</li>
        </ol>
    </div>
</div>
