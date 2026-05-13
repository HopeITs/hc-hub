{{-- @php
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
</div> --}}

{{-- @php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $Digits = fn ($s) => $forPdf ? ar_digits((string)$s) : (string)$s;

    $pos = $contract->position;
    $req = $pos?->requiredPosition;

    $positionTitleEn = $req?->title_en ?? $pos?->title_en ?? ('#'.$contract->position_id);
    $positionTitleAr = $req?->title_ar ?? $pos?->title_ar ?? ('#'.$contract->position_id);

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

    $LB = fn (string $t) => '<span class="h-label-bold">' . $t . '</span>';
    $p  = fn (string $t) => '<div>' . $t . '</div>';
@endphp

<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('4. Job description:') . ' <strong>' . $esc($positionTitleEn) . '</strong>'" 
                     :ar="$LB($Glyphs('4. وصف العمل:')) . ' <strong>' . $escAr($positionTitleAr) . '</strong>'"/>
</div>

<div class="avoid-break mt-6">
    <x-contract.dual :en="$LB('5. Duration of the agreement:') . ' One Year'" 
                     :ar="$LB($Glyphs('5. مدة العقد:')) . ' ' . $Glyphs('سنة واحدة')"/>
    
    <x-contract.dual rowClass="mt-1"
                     :en="'Starts from: <strong>'.$esc($startStr).'</strong>'"
                     :ar="$Glyphs('تبدأ من تاريخ:').' <strong>'.$esc($startStrAr).'</strong>'"/>
                     
    <x-contract.dual :en="'Ends on: <strong>'.$esc($endStr).'</strong>'"
                     :ar="$Glyphs('وتنتهي في تاريخ:').' <strong>'.$esc($endStrAr).'</strong>'"/>
</div>

<div class="avoid-break mt-6">
    <x-contract.dual :en="$LB('6. Volunteer Work Hours:')" :ar="$LB($Glyphs('6. ساعات العمل التطوعي:'))"/>
    <x-contract.dual :en="'<strong>'.$weeklyHours.'</strong> working hours per week, <strong>'.$dailyHours.'</strong> hours per day.'"
                     :ar="'<strong>'.$Digits($weeklyHours).'</strong> '.$Glyphs('ساعة عمل أسبوعياً،').' <strong>'.$Digits($dailyHours).'</strong> '.$Glyphs('ساعات يومياً.')"/>
    
    <x-contract.dual rowClass="mt-1" :en="'Standard office hours are Monday to Friday.'"
                     :ar="$Glyphs('الدوام الإداري: من الاثنين إلى الجمعة.')"/>
    
    <x-contract.dual :en="'From <strong>'.$dailyStart.'</strong> to <strong>'.$dailyEnd.'</strong>.'"
                     :ar="$Glyphs('من ').'<strong>'.$dailyStart.'</strong>'.$Glyphs(' حتى ').'<strong>'.$dailyEnd.'</strong>'"/>
    
    <x-contract.dual :en="'Saturdays are considered working days when necessary and not treated as extra hours.'"
                     :ar="$Glyphs('يُعتبر السبت يوماً للعمل عند الحاجة، ولا يُعتبر دواماً إضافياً.')"/>
</div>

<div class="avoid-break mt-6">
    <x-contract.dual :en="$LB('7. Stipend and Benefits:')" :ar="$LB($Glyphs('7. المكافأة المالية والمزايا:'))"/>
    <x-contract.dual
        :en="'Christian Hope Center grants a monthly stipend of / <strong>'.$salaryFormattedEn.' USD</strong> / or its equivalent in Syrian pounds, paid in cash at the end of each month.'"
        :ar="$Glyphs('يمنح مركز الرجاء المسيحي مكافأة شهرية مقدارها / ').'<strong>'.$salaryFormattedAr.'</strong>'.$Glyphs(' / دولار أمريكي أو ما يعادلها بالليرة السورية، وتُمنح نقداً في نهاية كل شهر.')"/>
</div>

<div class="avoid-break mt-6">
    <x-contract.dual :en="$LB('8. Duties:')" :ar="$LB($Glyphs('8. المسؤوليات:'))"/>
    <x-contract.dual :en="'As listed in the job description of the position (attached).'"
                     :ar="$Glyphs('حسب ما ورد في توصيف العمل التطوعي لهذا المنصب (مرفق).')"/>
</div> --}}


@php
    use App\Support\ArabicText as AR;
    use Carbon\Carbon;

    $forPdf = $forPdf ?? false;
    $Glyphs = fn($s) => $forPdf ? AR::glyphs((string) $s) : (string) $s;
    $Digits = fn($s) => $forPdf ? ar_digits((string) $s) : (string) $s;

    $pos = $contract->position;
    $req = $pos?->requiredPosition;
    $positionTitleEn = $req?->title_en ?? ($pos?->title_en ?? '—');
    $positionTitleAr = $req?->title_ar ?? ($pos?->title_ar ?? '—');

    $startStr = $contract->start_date ? Carbon::parse($contract->start_date)->format('d/m/Y') : '—';
    $endStr = $contract->end_date ? Carbon::parse($contract->end_date)->format('d/m/Y') : '—';

    $salary = (float) ($contract->salary ?? 0);
    $salaryFormattedEn = $salary ? number_format($salary, 0, '.', ',') : '—';
    $salaryFormattedAr = $salary ? $Digits(number_format($salary, 0, '.', ',')) : '—';

    $esc = fn($v) => e((string) ($v ?: '—'));
    $escAr = fn($v) => e((string) ($v ? $Glyphs($v) : '—'));
    $LB = fn($t) => '<span class="h-label-bold">' . $t . '</span>';
@endphp

<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('4. Job description:') . ' <strong>' . $esc($positionTitleEn) . '</strong>'" :ar="$LB($Glyphs('4. وصف العمل:')) . ' <strong>' . $escAr($positionTitleAr) . '</strong>'" />
</div>

<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('5. Duration of the agreement:') . ' One Year'" :ar="$LB($Glyphs('5. مدة العقد:')) . ' ' . $Glyphs('سنة واحدة')" />
    <x-contract.dual :en="'Starts from: <strong>' . $esc($startStr) . '</strong>'" :ar="$Glyphs('تبدأ من تاريخ:') . ' <strong>' . $Digits($startStr) . '</strong>'" />
    <x-contract.dual :en="'Ends on: <strong>' . $esc($endStr) . '</strong>'" :ar="$Glyphs('وتنتهي في تاريخ:') . ' <strong>' . $Digits($endStr) . '</strong>'" />
</div>

<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('6. Volunteer Work Hours:')" :ar="$LB($Glyphs('6. ساعات العمل التطوعي:'))" />
    <x-contract.dual :en="'<strong>' . ($contract->weekly_hours ?? 40) . '</strong> working hours per week.'" :ar="'<strong>' . $Digits($contract->weekly_hours ?? 40) . '</strong> ' . $Glyphs('ساعة عمل أسبوعياً.')" />
</div>

<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('7. Stipend and Benefits:')" :ar="$LB($Glyphs('7. المكافأة المالية والمزايا:'))" />
    <x-contract.dual :en="'Monthly stipend of / <strong>' . $salaryFormattedEn . ' USD</strong> / paid at end of month.'" :ar="$Glyphs('مكافأة شهرية مقدارها / ') . '<strong>' . $salaryFormattedAr . '</strong>' . $Glyphs(' / دولار أمريكي تُمنح نقداً.')" />
</div>

<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('8. Duties:')" :ar="$LB($Glyphs('8. المسؤوليات:'))" />
    <x-contract.dual :en="'As listed in the job description (attached).'" :ar="$Glyphs('حسب ما ورد في توصيف العمل التطوعي (مرفق).')" />
</div>

<div class="avoid-break mt-4">
    <x-contract.dual :en="$LB('9. Annual Leave:')" :ar="$LB($Glyphs('9. إجازات العمل التطوعي:'))" />
    <x-contract.dual class="mt-1" :en="'The second party is entitled to annual leave days proportional to the contract duration.'" :ar="$Glyphs('يحق للفريق الثاني يوم إجازة سنوية خلال فترة سريان العقد، وتكون متناسبة مع مدة العقد.')" />
    <x-contract.dual class="mt-2" :en="'24 days of sick leave (operations/infectious diseases): 12 days paid, 12 half-paid, with medical report.'" :ar="$Glyphs('يحق للفريق الثاني 24 يوم إجازة مرضية عند الضرورة (عمليات/أمراض معدية)، 12 يوم مدفوع و12 بنصف قيمة المكافأة بشرط تقرير طبي.')" />
    <x-contract.dual class="mt-2" :en="'Additional leave up to 12 days may be granted with deduction from stipend if limits exceeded.'" :ar="$Glyphs('في حال تجاوز الإجازات، يتم منحه إجازة لا تتجاوز 12 يوم يتم خصم قيمتها من المكافأة.')" />
</div>