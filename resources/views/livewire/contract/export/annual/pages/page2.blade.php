@php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;

    $pos = $contract->position;
    $req = $pos?->requiredPosition;

    $positionTitleEn = $req?->title ?? $pos?->title ?? ('#'.$contract->position_id);
    $positionTitleAr = $Glyphs($req?->title ?? $pos?->title ?? ('#'.$contract->position_id));

    // Dates (force English numbers)
    $startStr = $contract->start_date ? Carbon::parse($contract->start_date)->format('d/m/Y') : '—';
    $endStr   = $contract->end_date   ? Carbon::parse($contract->end_date)->format('d/m/Y')   : '—';

    $esc   = fn ($v) => e($v === '' || $v === null ? '—' : $v);
    $escAr = fn ($v) => e($v === '' || $v === null ? '—' : $Glyphs($v));

    $weeklyHours = (int) ($contract->weekly_hours ?? 40);
    $dailyHours = 8;
    $salary      = (float) ($contract->salary ?? 0);
    $salaryFormattedEn = $salary ? number_format($salary, 0, '.', ',') : '—';
    $salaryFormattedAr = $salary ? e(number_format($salary, 0, '.', ',')) : '—';
    $dailyStart = '9:30 AM';
    $dailyEnd = '5:30 PM';
    // Labels
    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';

    // Force English direction for numbers inside Arabic text
    $wrapLtr = fn ($v) => '<span dir="ltr">'.$v.'</span>';
@endphp

<div class="avoid-break mt-4">
    <x-contract.dual tight
                     :en="'Whereas Christian Hope Center Aleppo - Head office has agreed to this volunteering according to the conditions outlined in this contract,'"
                     :ar="$Glyphs(__('general.text11'))"
    />
    <x-contract.dual rowClass="mt-8"
                     :en="'This contract between the two parties witnesses mutual acceptance of the following:'"
                     :ar="$Glyphs(__('general.text12'))"
    />
</div>

<div class="avoid-break mt-4">
    <x-contract.dual
        :en="$L('Contract type:').'<strong> Volunteer agreement for limited period</strong>'"
        :ar="$Glyphs(__('general.text14')).' '.$L($Glyphs(__('general.text13')))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="$L('Place of Work:')"
                     :ar="$L($Glyphs(__('general.text15')))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="'<strong>Aleppo – Christian Hope Center – Head Office</strong>'"
                     :ar="$Glyphs(__('general.text16'))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="$L('Job description:').' '.$esc($positionTitleEn)"
                     :ar="$escAr($positionTitleAr).' '.$L($Glyphs(__('general.text17')))"
    />
</div>

<div class="avoid-break mt-4">
    <x-contract.dual
        :en="$L('Duration of the agreement:')"
        :ar="$L($Glyphs('مدة العقد:'))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="'One Year'"
                     :ar="$Glyphs('سنة')"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="'Starts from: '.$esc($startStr)"
                     :ar="$wrapLtr($esc($startStr)).' '.$Glyphs(__('general.text19'))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="'Ends on: '.$esc($endStr)"
                     :ar="$wrapLtr($esc($endStr)).' '.$Glyphs(__('general.text20'))"
    />
</div>

<div class="avoid-break mt-4">
    <x-contract.dual
        :en="$L('Volunteer Work Hours:')"
        :ar="$L($Glyphs('ساعات العمل التطوعي:'))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="$weeklyHours.' working hours per week, 8 hours per day.'"
                     :ar="$Glyphs(__('general.text23')).' '.$dailyHours.' '.$Glyphs(__('general.text22')).' '.$weeklyHours"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="'Standard office hours are Monday to Friday.'"
                     :ar="$Glyphs(__('general.text24'))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="'from 9:30 AM to 5:30 PM.'"
                     :ar="$dailyEnd.' '.$Glyphs(__('general.text26')).' '.$dailyStart.$Glyphs(__('general.text25'))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="'Saturdays are considered working days when necessary and not treated as extra hours.'"
                     :ar="$Glyphs('يعتبر يوم السبت يوم عمل في حال رأت الإدارة الحاجة لذلك، ولا يعتبر دوام إضافي.')"
    />
</div>

<div class="avoid-break ">
    <x-contract.dual
        :en="$L('Stipend and Benefits:')"
        :ar="$L($Glyphs('المكافأة المالية والمزايا:'))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="'Christian Hope Center is to give a monthly stipend of / '.$salaryFormattedEn.' USD / or its equivalent in Syrian pounds, to be paid in cash to the volunteer at the end of each month.'"
                     :ar="$Glyphs('يمنح مركز الرجاء المسيحي مكافأة شهرية مقدارها / ')
         .$wrapLtr($salaryFormattedEn)
         .$Glyphs(' / دولار أمريكي أو ما يعادلها بالليرة السورية، تمنح نقداً للمتطوع في نهاية كل شهر.')"
    />

</div>

<div class="avoid-break mt-4">
    <x-contract.dual
        :en="$L('Duties:')"
        :ar="$L($Glyphs('المسؤوليات:'))"
    />
    <x-contract.dual rowClass="mt-2"
                     :en="'As listed in the job description of the position (attached).'"
                     :ar="$Glyphs('حسب ما ورد في توصيف العمل التطوعي لهذا المنصب (مرفق).')"
    />
</div>
