@php
    use Illuminate\Support\Number;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;
    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';

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

    $roleEn = $t($contract->position?->requiredPosition, 'title', 'en',
         $t($contract->position, 'title', 'en', 'Web Developer'));
    $roleAr = $Glyphs($t($contract->position?->requiredPosition, 'title', 'ar',
         $t($contract->position, 'title', 'ar', 'مطوّر ويب')));

    $amount = $contract->salary ?? null;
    $amtEn = is_numeric($amount) ? Number::format($amount, locale: 'en') : '—';
    $amtAr = is_numeric($amount) ? ar_digits(Number::format($amount, locale: 'en')) : '—';

    $EN_THIRD_TERM_LABEL  = $L('Third term:');
    $AR_THIRD_TERM_LABEL  = $L($Glyphs('البند الثالث:'));

    $EN_FOURTH_TERM_LABEL = $L('Fourth term:');
    $AR_FOURTH_TERM_LABEL = $L($Glyphs('البند الرابع:'));

    $EN_LI_2 = "The second party is committed to delivering volunteering services as a {$roleEn} for 6 days per week, 6 hours a day (Working hours are determined in coordination with the direct manager).";
    $AR_LI_2 = "على الفريق الثاني تقديم خدماته التطوعية كـ{$roleAr} لمدة 6 أيام في الأسبوع، 6 ساعات يومياً (تُحدَّد ساعات العمل بالتنسيق مع المدير المباشر).";

    $EN_LI_3 = 'The second party is committed to delivering all equipment, materials, and documents he/she received to Hope Center immediately after the termination of this contract in accordance with the receive list of each volunteer which is regularly updated.';
    $AR_LI_3 = $Glyphs('يلتزم الفريق الثاني بتسليم جميع التجهيزات والمواد والوثائق التي استلمها فور انتهاء هذا العقد إلى مركز الرجاء، وذلك استناداً إلى لائحة الاستلام الخاصة بكل متطوع والتي يتم تحديثها دورياً.');

    $EN_LI_4 = "The first party is committed to paying the second party in exchange for volunteering services a monthly stipend of {$amtEn} SYP / Syrian Pounds only, to be paid in cash to the volunteer at the end of each month.";
    $AR_LI_4 = "يلتزم الفريق الأول بدفع مكافأة شهرية للفريق الثاني لقاء خدماته التطوعية مقدارها {$amtAr} ل.س / ليرة سورية فقط لا غير، تُدفع نقداً للمتطوع في نهاية كل شهر.";

    $EN_LI_5 = 'The second party has to commit, abide and sign the code of conduct.';
    $AR_LI_5 = $Glyphs('على الفريق الثاني الالتزام والتقيّد والتوقيع على قواعد السلوك الأخلاقية.');

    $EN_FOURTH_1 = 'The second party stipend amount will be deducted in case of repeated delays beyond working hours for more than 3 times a month, an amount equal to one work day cost.';
    $AR_FOURTH_1 = $Glyphs('يُحسم من المكافأة المالية للفريق الثاني في حال تكرار التأخر عن مواعيد الدوام المحددة أكثر من 3 مرات في الشهر، بمقدار يعادل كلفة يوم عمل واحد.');

    $EN_FOURTH_2 = 'If the second party has a strong reason to be absent, he/she must obtain the first party’s approval.';
    $AR_FOURTH_2 = $Glyphs('في حال اضطرار الفريق الثاني للغياب، فعليه الحصول على موافقة الفريق الأول بذلك.');
@endphp

<x-contract.dual rowClass="mt-1" :en="$EN_THIRD_TERM_LABEL" :ar="$AR_THIRD_TERM_LABEL"/>

<div class="dual-row mt-2">
    <div class="dual-col dual-en">
        <ol class="list-decimal pl-5 space-y-3" start="2">
            <li>{!! $EN_LI_2 !!}</li>
            <li>{!! $EN_LI_3 !!}</li>
            <li>{!! $EN_LI_4 !!}</li>
            <li>{!! $EN_LI_5 !!}</li>
        </ol>
    </div>
    <div class="dual-col dual-ar">
        <ol class="list-decimal pr-5 space-y-3" start="2" style="direction: rtl; text-align: right;">
            <li>{!! $AR_LI_2 !!}</li>
            <li>{!! $AR_LI_3 !!}</li>
            <li>{!! $AR_LI_4 !!}</li>
            <li>{!! $AR_LI_5 !!}</li>
        </ol>
    </div>
</div>

<x-contract.dual rowClass="mt-4" :en="$EN_FOURTH_TERM_LABEL" :ar="$AR_FOURTH_TERM_LABEL"/>

<div class="dual-row mt-2">
    <div class="dual-col dual-en">
        <ol class="list-decimal pl-5 space-y-3">
            <li>{!! $EN_FOURTH_1 !!}</li>
            <li>{!! $EN_FOURTH_2 !!}</li>
        </ol>
    </div>
    <div class="dual-col dual-ar">
        <ol class="list-decimal pr-5 space-y-3" style="direction: rtl; text-align: right;">
            <li>{!! $AR_FOURTH_1 !!}</li>
            <li>{!! $AR_FOURTH_2 !!}</li>
        </ol>
    </div>
</div>
