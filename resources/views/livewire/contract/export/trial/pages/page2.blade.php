{{-- @php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;
    $Glyphs = fn($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $L = fn($t) => '<span class="h-label">'.$t.'</span>';
    $esc = fn($v) => e($v === '' || $v === null ? '—' : $v);
    $escAr = fn($v) => e($v === '' || $v === null ? '—' : $Glyphs($v));

    $dailyStart = '9:00 AM';
    $dailyEnd   = '5:30 PM';

    $start = $contract->start_date ? Carbon::parse($contract->start_date) : null;
    $end   = $contract->end_date   ? Carbon::parse($contract->end_date)   : null;
    $startStr = $start ? $start->format('d/m/Y') : '/';
    $endStr   = $end   ? $end->format('d/m/Y')   : '/';
    $months   = ($start && $end) ? max(1, $start->diffInMonths($end)) : 3;

    $durationEn = match($months){1=>'One Month',2=>'Two Months',3=>'Three Months',4=>'Four Months',5=>'Five Months',6=>'Six Months',default=>$months.' Months'};
    $durationAr = match($months){1=>'شهر واحد',2=>'شهران',3=>'ثلاثة أشهر',4=>'أربعة أشهر',5=>'خمسة أشهر',6=>'ستة أشهر',default=>$months.' أشهر'};

    $position = $contract->position;
    $program  = $position?->program->name ?? '—';
    $positionEN = $position?->title_en ?? '—';
    $positionAR = $position?->title_ar ?? '—';
    $placeEn = $program;
    $placeAr = $program;

    // helper for numbering list
    $asList = fn(array $lines,string $lang,int $start=1)=>
        '<ul style="margin:0;padding:0;list-style:none;">'.
        implode('',array_map(function($l,$i)use($lang,$start){
            $num=$start+$i;
            return $lang==='ar'
                ? '<li style="direction:rtl;text-align:right;position:relative;padding-right:18px;padding-bottom:68px;"><span style="position:absolute;right:0;">.'.$num.'</span>'.$l.'</li>'
                : '<li style="position:relative;padding-left:18px;padding-bottom:14px;"><span style="position:absolute;left:0;">'.$num.'.</span>'.$l.'</li>';
        },$lines,array_keys($lines))).
        '</ul>';

    $EN_LIST_3 = [
        'The contract period is '.$durationEn.' starting from '.$startStr.' until '.$endStr.', with a possibility to be extended in agreement between the two parties and according to the performance evaluation, under the condition of not exceeding six months period.',
        'The trial period ends by confirming a permanent contract with the volunteer or dismissing his/her services according to a clear performance evaluation.',
        'The second party is committed to delivering volunteering services as a <strong>'.$esc($positionEN).'</strong>. <br>Standard office hours are Monday to Friday, from '.$dailyStart.' to '.$dailyEnd.'. <br><strong>Place of Work:</strong> '.$placeEn.'<Br><br><br>',
        'Saturdays are considered working days when necessary and not treated as extra hours.<br><Br><Br>',
        'The second party (the volunteer) is committed to delivering all equipment, materials and documents he/she received immediately to the Hope Center after the termination of this contract according to the receive list of every volunteer, which is regularly updated.',
    ];

    $AR_LIST_3 = [
        $Glyphs(__('general.text39',['duration'=>$durationAr,'start'=>$startStr,'end'=>$endStr])),
        $Glyphs(__('general.text40')),
        $Glyphs(__('general.text41',['position'=>$positionAR,'program'=>$program])).'<br>'.$Glyphs(__('general.text24')).'<br>'.$dailyEnd.' '.$Glyphs(__('general.text26')).' '.$dailyStart.$Glyphs(__('general.text25')).'<br>'.$Glyphs('  : مكان العمل<br>').$placeAr,
        $Glyphs(__('general.text42')),
        $Glyphs(__('general.text43')),
    ];
@endphp

<div class="avoid-break mt-4">
    <x-contract.dual :en="$L('Third term:')" :ar="$L($Glyphs('البند الثالث:'))"/>
</div>

<x-contract.dual rowClass="mt-6"
                 :en="$asList($EN_LIST_3,'en',1)"
                 :ar="$asList($AR_LIST_3,'ar',1)"/> --}}


@php
    use App\Support\ArabicText as AR;
    use Carbon\Carbon;

    $forPdf = $forPdf ?? false;
    $Glyphs = fn($s) => $forPdf ? AR::glyphs((string) $s) : (string) $s;
    $SmartGlyphs = fn($s, $limit = 2000) => $forPdf ? AR::smartGlyphs((string) $s, $limit) : (string) $s;
    $Digits = fn($s) => $forPdf ? AR::digits((string) $s) : (string) $s;

    $pos = $contract->position;
    $req = $pos?->requiredPosition;
    $positionTitleEn = $req?->title_en ?? ($pos?->title_en ?? '—');
    $positionTitleAr = $req?->title_ar ?? ($pos?->title_ar ?? '—');

    $startStr = $contract->start_date ? Carbon::parse($contract->start_date)->format('d/m/Y') : '—';
    $endStr = $contract->end_date ? Carbon::parse($contract->end_date)->format('d/m/Y') : '—';
    $startStrAr = $startStr;
    $endStrAr = $endStr;

    $salary = (float) ($contract->salary ?? 0);
    $salaryFormattedEn = $salary ? number_format($salary, 0, '.', ',') : '—';
    $salaryFormattedAr = $salary ? number_format($salary, 0, '.', ',') : '—';

    $weeklyHours = (int) ($contract->weekly_hours ?? 40);
    $dailyHours = $weeklyHours / 5;

    $workDaysEn = $contract->work_days_en ?: 'Monday to Friday';
    $workDaysAr = $contract->work_days_ar ?: 'الإثنين إلى الجمعة';

    $dailyStart = $contract->work_hours_from ? Carbon::parse($contract->work_hours_from)->format('g:i A') : '9:00 AM';
    $dailyEnd = $contract->work_hours_to ? Carbon::parse($contract->work_hours_to)->format('g:i A') : '5:00 PM';

    $esc = fn($v) => e((string) ($v ?: '—'));
    $escAr = fn($v) => e((string) ($v ? $Glyphs($v) : '—'));
    $LB = fn($t) => '<span class="h-label-bold">' . $t . '</span>';
    $wrapLtr = fn($v) => '<span dir="ltr">' . $v . '</span>';

    $programId = $contract->position?->program_id;
    $isStudyZone = $programId == 8;
    $isGeneralProject = !$isStudyZone;
@endphp



<div class="avoid-break mt-2">
    <x-contract.dual :en="$LB('4. Job description:')" :ar="$LB($Glyphs('وصف العمل:') . ' .4')" />
    <x-contract.dual rowClass="mt-1 lh-tight" :en="'<strong>' . $esc($positionTitleEn) . '</strong>'"
        :ar="$SmartGlyphs('<strong>'.$esc($positionTitleAr).
            '</strong>')" />
</div>

<div class="avoid-break mt-2">
    <x-contract.dual :en="$LB('5. Duration of the contract:')" :ar="$LB($Glyphs('مدة العقد (الفترة التجريبية):') . ' .5')" />

    <x-contract.dual rowClass="mt-1 lh-tight" :en="'Start date: <strong>' . $esc($startStr) . '</strong>'"
        :ar="$Glyphs('تبدأ من تاريخ: <strong>'.$esc($startStrAr).
            '</strong>')" />

    <x-contract.dual :en="'End date: <strong>' . $esc($endStr) . '</strong>'" :ar="$Glyphs('وتنتهي إلى تاريخ: <strong>'.$esc($endStrAr).
        '</strong>')" />

    <x-contract.dual rowClass="mt-1 lh-tight" :en="'It can be extended by mutual agreement between the two parties, based on performance evaluation, provided that the trial period does not exceed six months. The trial period ends with either the confirmation of the second team with a voluntary contract or their dismissal, based on a clear performance evaluation.'" :ar="$SmartGlyphs('وهي قابلة للتمديد بالاتفاق بين الفريقين ووفقاً لتقييم الأداء،') .
        '<br>' .
        $SmartGlyphs('على ألا تتجاوز مدة الفترة التجريبية ستة أشهر. تنتهي الفترة') .
        '<br>' .
        $SmartGlyphs('التجريبية بتثبيت الفريق الثاني بعقد تطوّع أو الاعتذار منه') .
        '<br>' .
        $SmartGlyphs('وذلك وفق تقييم أداء واضح.')" />
</div>

<div class="avoid-break mt-2">
    <x-contract.dual :en="$LB('6. Volunteer Work Hours:')" :ar="$LB($Glyphs('ساعات العمل التطوعّي:') . ' .6')" />

    <x-contract.dual rowClass="mt-1 lh-tight" :en="'<strong>' .
        $weeklyHours .
        ' working hours</strong> per week, <strong>' .
        $dailyHours .
        ' hours</strong> per day.'"
        :ar="$SmartGlyphs('ساعات يومياً.').
        ' <strong>'.$dailyHours.
        '</strong> '.$SmartGlyphs('ساعة عمل أسبوعياً،').
        ' <strong>'.$weeklyHours.
        '</strong>'" />

    <x-contract.dual rowClass="mt-1 lh-tight" :en="'Standard office hours are ' . $workDaysEn . ' from ' . $dailyStart . ' to ' . $dailyEnd . '.'" :ar="$SmartGlyphs('من نهار ' . $workDaysAr) .
        '<br>' .
        '. ' .
        $wrapLtr($dailyEnd) .
        ' ' .
        $SmartGlyphs('حتى الساعة') .
        ' ' .
        $wrapLtr($dailyStart) .
        ' ' .
        $SmartGlyphs('من الساعة')" />

    @if ($isGeneralProject)
        <x-contract.dual rowClass="mt-1 lh-tight" :en="'Saturdays are considered working days when necessary and not treated as extra hours.'" :ar="$SmartGlyphs('يعتبر يوم السبت يوم عمل في حال رأت الإدارة الحاجة<br>لذلك، ولا يعتبر دوام إضافي.')" />
    @endif

    @if ($isStudyZone)
        <x-contract.dual rowClass="mt-1 lh-tight" :en="'Volunteers are entitled to a second day off in the week, to be determined in coordination with management and based on work requirements. It is permitted to adjust working hours and holidays in months that require opening the center 7 days, in coordination with the direct manager and the HR officer.'" :ar="$SmartGlyphs('يحق للمتطوع يوم عطلة ثان ضمن الأسبوع، يتم تحديده') .
            '<br>' .
            $SmartGlyphs('بالتنسيق مع الإدارة وما يتطلبه العمل. يسمح بتعديل أوقات') .
            '<br>' .
            $SmartGlyphs('الدوام والعطل في الأشهر التي تتطلب فتح المركز 7 أيام،') .
            '<br>' .
            $SmartGlyphs('وذلك بالتنسيق مع المدير المباشر ومسؤول الموارد البشرية.')" />
    @endif
</div>

<div class="avoid-break mt-2">
    <x-contract.dual :en="$LB('7. Stipend and Benefits:')" :ar="$LB($Glyphs('المكافأة المالية والمزايا:') . ' .7')" />

    <x-contract.dual rowClass="mt-1 lh-tight" :en="'Christian Hope Center is to give a monthly stipend of<br><strong>/ ' .
        $salaryFormattedEn .
        ' USD /</strong>'"
        :ar="$SmartGlyphs('خصص الفريق الأول للفريق الثاني شهرياً مكافأة وقدرها').
        '<br><strong>/ '.$Glyphs('دولار أمريكي').
        ' '.$salaryFormattedAr.
        ' /</strong>'" />

    <x-contract.dual :en="'or its equivalent in Syrian pounds, to be paid in cash to the volunteer at the end of each month.'" :ar="$SmartGlyphs('أو ما يعادلها بالليرة السورية، تُمنح نقداً في نهاية كل شهر.')" />
</div>

<div class="avoid-break mt-2">
    <x-contract.dual :en="$LB('8. Duties:')" :ar="$LB($Glyphs('المسؤوليات:') . ' .8')" />
    <x-contract.dual rowClass="mt-1 lh-tight" :en="'The second party commits to the description of the volunteer work approved by the first team. Therefore, their signature on this contract is an acknowledgment of their full commitment to it.'" :ar="$SmartGlyphs(
        'يلتزم الفريق الثاني بتوصيف العمل التطوّعي الذي أقرّه<br>الفريق الأول، وعليه فإن توقيعه على هذا العقد<br>هو إقرار منه بالتزامه الكامل بها.',
    )" />
</div>
