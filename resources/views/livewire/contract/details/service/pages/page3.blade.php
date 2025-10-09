@php
    use App\Support\ArabicText as AR;

    $forPdf  = $forPdf ?? false;
    $Glyphs  = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $Digits  = fn ($s) => $forPdf ? ar_digits((string)$s) : (string)$s;
    $L       = fn (string $t) => '<span class="h-label">'.$t.'</span>';

    // Helper for translations
    $t = function ($model, string $field, string $lang, $fallback = null) {
        if (! $model) return $fallback;
        if (method_exists($model, 'getTranslation')) {
            $v = $model->getTranslation($field, $lang);
            return ($v === null || $v === '') ? $fallback : $v;
        }
        $raw = $model->{$field} ?? null;
        if (is_array($raw)) {
            $v = $raw[$lang] ?? reset($raw);
            return ($v === null || $v === '') ? $fallback : $v;
        }
        return ($raw === null || $raw === '') ? $fallback : $raw;
    };

    // Role
    $roleEn = $t($contract->position?->requiredPosition, 'title', 'en',
         $t($contract->position, 'title', 'en', 'Web Developer'));
    $roleAr = $t($contract->position?->requiredPosition, 'title', 'ar',
         $t($contract->position, 'title', 'ar', 'مطوّر ويب'));

    // Salary
    $salary = (float) ($contract->salary ?? 0);
    $salaryFormattedEn = $salary ? number_format($salary, 0, '.', ',') : '—';
    $salaryFormattedAr = $salary ? $Digits(number_format($salary, 0, '.', ',')) : '—';

    // Currency mapping
    $CURRENCY = [
        1     => ['code' => 'SYP', 'en' => 'Syrian Pounds', 'ar' => 'ليرة سورية'],
        2     => ['code' => 'USD', 'en' => 'US Dollars',    'ar' => 'دولار أمريكي'],
        3     => ['code' => 'EUR', 'en' => 'European Euro', 'ar' => 'يورو أوروبي'],
        'SYP' => ['code' => 'SYP', 'en' => 'Syrian Pounds', 'ar' => 'ليرة سورية'],
        'USD' => ['code' => 'USD', 'en' => 'US Dollars',    'ar' => 'دولار أمريكي'],
        'EUR' => ['code' => 'EUR', 'en' => 'European Euro', 'ar' => 'يورو أوروبي'],
    ];

    $currencyKey = $contract->currency ?? 1; // could be ID or code
    $cur = $CURRENCY[$currencyKey] ?? $CURRENCY[1];
@endphp

{{-- Third term --}}
<div class="avoid-break mt-4">
    <x-contract.dual :en="$L('Third term:')" :ar="$L($Glyphs('البند الثالث:'))"/>
</div>

{{-- 2 --}}
<x-contract.dual rowClass="mt-6 mb-6"
                 :en="'2. The second party is committed to delivering volunteering services as a '.$roleEn.' for 6 days per week, 6 hours a day (Working hours are determined in coordination with the direct manager).'"
                 :ar="'2. على الفريق الثاني تقديم خدماته التطوعية كـ'.$roleAr.' لمدة 6 أيام في الأسبوع، 6 ساعات يومياً (تُحدَّد ساعات العمل بالتنسيق مع المدير المباشر).'"
/>

{{-- 3 --}}
<x-contract.dual rowClass="mt-6 mb-6"
                 :en="'3. The second party is committed to delivering all equipment, materials, and documents he/she received to Hope Center immediately after the termination of this contract in accordance with the receive list of each volunteer which is regularly updated.'"
                 :ar="'3. يلتزم الفريق الثاني بتسليم جميع التجهيزات والمواد والوثائق التي استلمها فور انتهاء هذا العقد إلى مركز الرجاء، وذلك استناداً إلى لائحة الاستلام الخاصة بكل متطوع والتي يتم تحديثها دورياً.'"
/>

{{-- 4 --}}
<x-contract.dual rowClass="mt-6 mb-6"
                 :en="'4. The first party is committed to paying the second party in exchange for volunteering services a monthly stipend of '.$salaryFormattedEn.' '.$cur['code'].' / '.$cur['en'].' only, to be paid in cash to the volunteer at the end of each month.'"
                 :ar="'4. يلتزم الفريق الأول بدفع مكافأة شهرية للفريق الثاني لقاء خدماته التطوعية مقدارها '.$salaryFormattedAr.' '.$cur['code'].' / '.$cur['ar'].' فقط لا غير، تُدفع نقداً للمتطوع في نهاية كل شهر.'"
/>

{{-- 5 --}}
<x-contract.dual rowClass="mt-6 mb-10"
                 :en="'5. The second party has to commit, abide and sign the code of conduct.'"
                 :ar="'5. على الفريق الثاني الالتزام والتقيد والتوقيع على قواعد السلوك الأخلاقية.'"
/>

{{-- Fourth term --}}
<div class="avoid-break mt-6">
    <x-contract.dual :en="$L('Fourth term:')" :ar="$L($Glyphs('البند الرابع:'))"/>
</div>

{{-- 1 --}}
<x-contract.dual rowClass="mt-6 mb-6"
                 :en="'1. The second party stipend amount will be deducted in case of repeated delays beyond working hours for more than 3 times a month, an amount equal to one work day cost.'"
                 :ar="'1. يُحسم من المكافأة المالية للفريق الثاني في حال تكرار التأخر عن مواعيد الدوام المحددة أكثر من 3 مرات في الشهر، بمقدار يعادل كلفة يوم عمل واحد.'"
/>

{{-- 2 --}}
<x-contract.dual rowClass="mt-6 mb-10"
                 :en="'2. If the second party has a strong reason to be absent, he/she must obtain the first party’s approval.'"
                 :ar="'2. في حال اضطرار الفريق الثاني للغياب، فعليه الحصول على موافقة الفريق الأول بذلك.'"
/>
