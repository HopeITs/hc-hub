@php
    use App\Support\ArabicText as AR;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $forPdf = $forPdf ?? false;
    $G = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $D = fn ($s) => $forPdf ? AR::digits((string)$s) : (string)$s;

    $loc = $locale ?? app()->getLocale();

    $pos  = $contract->position;
    $req  = $pos?->requiredPosition;

    $positionTitleEn = $req?->title
        ?? $pos?->getTranslation('title', 'en')
        ?? $pos?->title
        ?? ('#'.$contract->position_id);

    $positionTitleAr = $G(
        $pos?->getTranslation('title', 'ar')
        ?? $req?->title
        ?? $pos?->title
        ?? ('#'.$contract->position_id)
    );

    $start    = $contract->start_date;
    $end      = $contract->end_date;
    $startStr = $start ? Carbon::parse($start)->format('d/m/Y') : '—';
    $endStr   = $end   ? Carbon::parse($end)->format('d/m/Y')   : '—';
    $startStrAr = $D($startStr);
    $endStrAr   = $D($endStr);

    $weeklyHours = (int) ($contract->weekly_hours ?? 40);
    $salary      = (float) ($contract->salary ?? 0);
    $salaryFormattedEn = $salary ? number_format($salary, 0, '.', ',') : '—';
    $salaryFormattedAr = $salary ? $D(number_format($salary, 0, '.', ',')) : '—';

    $esc   = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));
    $escAr = fn ($v) => e((string)($v === '' || $v === null ? '—' : $G($v)));
    $L     = fn (string $txt) => '<span class="h-label">'.$txt.'</span>';
@endphp

<div class="avoid-break">
    <x-contract.dual tight
                     :en="'whereas “Christian Hope Center Aleppo / Head office” has agreed to this volunteering according to the conditions outlined in this contract.'"
                     :ar="$G('وبما أن \"مركز الرجاء المسيحي - حلب - الإدارة العامة\" قد وافق على هذا التطوع وفق الشروط المنصوص عليها في هذا العقد.')"
    />
    <x-contract.dual rowClass="mt-3"
                     :en="'This contract between the two parties witnesses mutual acceptance of the following:'"
                     :ar="$G('تم العقد بين الطرفين والقبول المشترك لما يلي:')"
    />
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-4"
                     :en="$L('Contract type:').' Volunteer agreement for a limited period'"
                     :ar="$L($G('نوع العقد:')).' '.$G('عقد تطوعي لمدة محدودة')"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="$L('Place of Work:').' Aleppo – Christian Hope Center – Head Office'"
                     :ar="$L($G('مكان العمل:')).' '.$G('حلب – مركز الرجاء المسيحي – المكتب')"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="$L('Job description:').' '.$esc($positionTitleEn)"
                     :ar="$L($G('وصف العمل:')).' '.$esc($positionTitleAr)"
    />
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-4"
                     :en="$L('Duration of the agreement:').'<div>One Year</div><div>Starts from: '.$esc($startStr).'</div><div>Ends on: '.$esc($endStr).'</div>'"
                     :ar="$L($G('مدة العقد:')).'<div>'.$G('سنة').'</div><div>'.$G('تبدأ من تاريخ:').' '.$esc($startStrAr).'</div><div>'.$G('وتنتهي في التاريخ:').' '.$esc($endStrAr).'</div>'"
    />
</div>

@php
    $EN_HOURS_LIST = [
        'Volunteer Work Hours:',
        $weeklyHours.' working hours per week, 8 hours per day.',
        'Standard office hours: Monday–Friday, 09:30–17:30.',
        'Saturdays may be working days when necessary and are not treated as extra hours.',
    ];
    $AR_HOURS_LIST = [
        $G('ساعات العمل التطوعي:'),
        $D($weeklyHours).' '.$G('ساعة عمل أسبوعياً، 8 ساعات يومياً.'),
        $G('الدوام الإداري: من الاثنين إلى الجمعة 09:30–17:30.'),
        $G('يُعتبر السبت يوماً للعمل عند الحاجة، ولا يُعتبر دواماً إضافياً.'),
    ];
    $asList = function(array $lines) {
        return '<ul style="margin:0; padding-left:14px;">'
             . implode('', array_map(fn($l) => '<li>'.$l.'</li>', $lines))
             . '</ul>';
    };
@endphp

<div class="avoid-break">
    <x-contract.dual rowClass="mt-4"
                     :en="$asList($EN_HOURS_LIST)"
                     :ar="$asList($AR_HOURS_LIST)"
    />
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-4"
                     :en="$L('Stipend and Benefits:').'<div>Christian Hope Center grants a monthly stipend of / '.$esc($salaryFormattedEn).' USD / or its equivalent in Syrian pounds, paid in cash at the end of each month.</div>'"
                     :ar="$L($G('المكافأة المالية والمزايا:')).'<div>'.$G('يمنح مركز الرجاء المسيحي مكافأة شهرية مقدارها / ').$esc($salaryFormattedAr).$G(' / دولار أمريكي أو ما يعادلها بالليرة السورية، وتُمنح نقداً في نهاية كل شهر.').'</div>'"
    />
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-4"
                     :en="$L('Duties:').'<div>As listed in the job description of the position (attached).</div>'"
                     :ar="$L($G('المسؤوليات:')).'<div>'.$G('حسب ما ورد في توصيف العمل التطوعي لهذا المنصب (مرفق).').'</div>'"
    />
</div>
