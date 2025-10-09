@php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $Digits = fn ($s) => $forPdf ? ar_digits((string)$s) : (string)$s;

    $pos = $contract->position;
    $req = $pos?->requiredPosition;

    $positionTitleEn = $req?->title_en ?? $pos?->title_en ?? ('#'.$contract->position_id);
    $positionTitleAr = $Glyphs($req?->title_ar ?? $pos?->title_ar ?? ('#'.$contract->position_id));

    // Dates
    $startStr   = $contract->start_date ? Carbon::parse($contract->start_date)->format('d/m/Y') : '—';
    $endStr     = $contract->end_date   ? Carbon::parse($contract->end_date)->format('d/m/Y')   : '—';
    $startStrAr = $Digits($startStr);
    $endStrAr   = $Digits($endStr);

    // Hours & stipend
    $weeklyHours = (int) ($contract->weekly_hours ?? 40);
    $dailyHours  = 8;
    $salary      = (float) ($contract->salary ?? 0);
    $salaryFormattedEn = $salary ? number_format($salary, 0, '.', ',') : '—';
    $salaryFormattedAr = $salary ? $Digits(number_format($salary, 0, '.', ',')) : '—';
    $dailyStart = '9:30 AM';
    $dailyEnd   = '5:30 PM';

    $esc   = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));
    $escAr = fn ($v) => e((string)($v === '' || $v === null ? '—' : $Glyphs($v)));

    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';
    $wrapLtr = fn ($v) => '<span dir="ltr">'.$v.'</span>';
@endphp


<div class="avoid-break " >
    <x-contract.dual tight
                     :en="'Whereas Christian Hope Center Aleppo - Head office has agreed to this volunteering according to the conditions outlined in this contract,'"
                     :ar="$Glyphs('وبما أن مركز الرجاء المسيحي - حلب - الإدارة العامة قد وافق على هذا التطوع وفق الشروط المنصوص عليها في هذا العقد.')"/>
    <x-contract.dual rowClass="mt-3"
                     :en="'This contract between the two parties witnesses mutual acceptance of the following:'"
                     :ar="$Glyphs('تم العقد بين الطرفين والقبول المشترك لما يلي:')"/>
</div>

<div class="avoid-break mt-4" style="line-height: 1" >
    <x-contract.dual :en="$L('Contract type:')" :ar="$L($Glyphs('نوع العقد:'))"/>
    <x-contract.dual :en="'Volunteer agreement for a limited period'"
                     :ar="$Glyphs('عقد تطوعي لمدة محدودة')"/>

    <x-contract.dual :en="$L('Place of Work:')" :ar="$L($Glyphs('مكان العمل:'))"/>
    <x-contract.dual :en="'Aleppo – Christian Hope Center – Head Office'"
                     :ar="$Glyphs('حلب – مركز الرجاء المسيحي – المكتب الرئيسي')"/>

    <x-contract.dual :en="$L('Job description:')" :ar="$L($Glyphs('وصف العمل:'))"/>
    <x-contract.dual :en="$esc($positionTitleEn)"
                     :ar="$escAr($positionTitleAr)"/>
</div>

<div class="avoid-break mt-6" >
    <x-contract.dual :en="$L('Duration of the agreement:')" :ar="$L($Glyphs('مدة العقد:'))"/>
    <x-contract.dual :en="'One Year'" :ar="$Glyphs('سنة')"/>
    <x-contract.dual :en="'Starts from: '.$esc($startStr)"
                     :ar="$Glyphs('تبدأ من تاريخ:').' '.$wrapLtr($esc($startStrAr))"/>
    <x-contract.dual :en="'Ends on: '.$esc($endStr)"
                     :ar="$Glyphs('وتنتهي من تاريخ:').' '.$wrapLtr($esc($endStrAr))"/>
</div>

<div class="avoid-break mt-6" style="line-height: 1.1" >
    <x-contract.dual :en="$L('Volunteer Work Hours:')" :ar="$L($Glyphs('ساعات العمل التطوعي:'))"/>
    <x-contract.dual :en="$weeklyHours.' working hours per week, '.$dailyHours.' hours per day.'"
                     :ar="$Digits($weeklyHours).' '.$Glyphs('ساعة عمل أسبوعياً،').' '.$Digits($dailyHours).' '.$Glyphs('ساعات يومياً.')"/>
    <x-contract.dual :en="'Standard office hours are Monday to Friday.'"
                     :ar="$Glyphs('الدوام الإداري: من الاثنين إلى الجمعة.')"/>
    <x-contract.dual :en="'From '.$dailyStart.' to '.$dailyEnd.'.'"
                     :ar="$Glyphs('من ').$dailyStart.$Glyphs(' حتى ').$dailyEnd"/>
    <x-contract.dual :en="'Saturdays are considered working days when necessary and not treated as extra hours.'"
                     :ar="$Glyphs('يُعتبر السبت يوماً للعمل عند الحاجة، ولا يُعتبر دواماً إضافياً.')"/>
</div>

<div class="avoid-break mt-6" >
    <x-contract.dual :en="$L('Stipend and Benefits:')" :ar="$L($Glyphs('المكافأة المالية والمزايا:'))"/>
    <x-contract.dual
        :en="'Christian Hope Center grants a monthly stipend of / '.$salaryFormattedEn.' USD / or its equivalent in Syrian pounds, paid in cash at the end of each month.'"
        :ar="$Glyphs('يمنح مركز الرجاء المسيحي مكافأة شهرية مقدارها / ').$salaryFormattedAr.$Glyphs(' / دولار أمريكي أو ما يعادلها بالليرة السورية، وتُمنح نقداً في نهاية كل شهر.')"/>
</div>

<div class="avoid-break mt-6">
    <x-contract.dual :en="$L('Duties:')" :ar="$L($Glyphs('المسؤوليات:'))"/>
    <x-contract.dual :en="'As listed in the job description of the position (attached).'"
                     :ar="$Glyphs('حسب ما ورد في توصيف العمل التطوعي لهذا المنصب (مرفق).')"/>
</div>
