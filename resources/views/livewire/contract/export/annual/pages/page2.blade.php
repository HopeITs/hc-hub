@php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $Digits = fn ($s) => $forPdf ? AR::digits((string)$s) : (string)$s;

    $pos = $contract->position;
    $req = $pos?->requiredPosition;

    $positionTitleEn = $req?->title ?? $pos?->title ?? ('#'.$contract->position_id);
    $positionTitleAr = $Glyphs($req?->title ?? $pos?->title ?? ('#'.$contract->position_id));

    $startStr   = $contract->start_date ? Carbon::parse($contract->start_date)->format('d/m/Y') : '—';
    $endStr     = $contract->end_date   ? Carbon::parse($contract->end_date)->format('d/m/Y')   : '—';
    $startStrAr = $Digits($startStr);
    $endStrAr   = $Digits($endStr);

    $weeklyHours = (int) ($contract->weekly_hours ?? 40);
    $salary      = (float) ($contract->salary ?? 0);
    $salaryFormattedEn = $salary ? number_format($salary, 0, '.', ',') : '—';
    $salaryFormattedAr = $salary ? $Digits(number_format($salary, 0, '.', ',')) : '—';

    $esc   = fn ($v) => e($v === '' || $v === null ? '—' : $v);
    $escAr = fn ($v) => e($v === '' || $v === null ? '—' : $Glyphs($v));

    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';
@endphp

<div class="avoid-break">
    <x-contract.dual tight
                     :en="'Whereas Christian Hope Center Aleppo / Head office has agreed to this volunteering according to the conditions outlined in this contract.'"
                     :ar="$Glyphs('وبما أن مركز الرجاء المسيحي - حلب - الإدارة العامة قد وافق على هذا التطوع وفق الشروط المنصوص عليها في هذا العقد.')"
    />
    <x-contract.dual rowClass="mt-3"
                     :en="'This contract between the two parties witnesses mutual acceptance of the following:'"
                     :ar="$Glyphs('تم العقد بين الطرفين والقبول المشترك لما يلي:')"
    />
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-4"
                     :en="$L('Contract type:').' Volunteer agreement for a limited period'"
                     :ar="$L($Glyphs('نوع العقد:')).' '.$Glyphs('عقد تطوعي لمدة محدودة')"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="$L('Place of Work:').' Aleppo – Christian Hope Center – Head Office'"
                     :ar="$L($Glyphs('مكان العمل:')).' '.$Glyphs('حلب – مركز الرجاء المسيحي – المكتب الرئيسي')"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="$L('Job description:').' '.$esc($positionTitleEn)"
                     :ar="$L($Glyphs('وصف العمل:')).' '.$escAr($positionTitleAr)"
    />
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-4"
                     :en="$L('Duration of the agreement:').'<div>One Year</div><div>Starts from: '.$esc($startStr).'</div><div>Ends on: '.$esc($endStr).'</div>'"
                     :ar="$L($Glyphs('مدة العقد:')).'<div>'.$Glyphs('سنة').'</div><div>'.$Glyphs('تبدأ من تاريخ:').' '.$escAr($startStrAr).'</div><div>'.$Glyphs('وتنتهي في التاريخ:').' '.$escAr($endStrAr).'</div>'"
    />
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-4"
                     :en="$L('Volunteer Work Hours:')"
                     :ar="$L($Glyphs('ساعات العمل التطوعي:'))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="'• '.$weeklyHours.' working hours per week, 8 hours per day.'"
                     :ar="'• '.$Digits($weeklyHours).' '.$Glyphs('ساعة عمل أسبوعياً، 8 ساعات يومياً.')"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="'• Standard office hours: Monday–Friday, 09:30–17:30.'"
                     :ar="'• '.$Glyphs('الدوام الإداري: من الاثنين إلى الجمعة 09:30–17:30.')"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="'• Saturdays may be working days when necessary and are not treated as extra hours.'"
                     :ar="'• '.$Glyphs('يُعتبر السبت يوماً للعمل عند الحاجة، ولا يُعتبر دواماً إضافياً.')"
    />
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-4"
                     :en="$L('Stipend and Benefits:').'<div>Christian Hope Center grants a monthly stipend of / '.$esc($salaryFormattedEn).' USD / or its equivalent in Syrian pounds, paid in cash at the end of each month.</div>'"
                     :ar="$L($Glyphs('المكافأة المالية والمزايا:')).'<div>'.$Glyphs('يمنح مركز الرجاء المسيحي مكافأة شهرية مقدارها / ').$escAr($salaryFormattedAr).$Glyphs(' / دولار أمريكي أو ما يعادلها بالليرة السورية، وتُمنح نقداً في نهاية كل شهر.').'</div>'"
    />
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-4"
                     :en="$L('Duties:').'<div>As listed in the job description of the position (attached).</div>'"
                     :ar="$L($Glyphs('المسؤوليات:')).'<div>'.$Glyphs('حسب ما ورد في توصيف العمل التطوعي لهذا المنصب (مرفق).').'</div>'"
    />
</div>
