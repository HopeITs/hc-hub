@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $loc = $locale ?? app()->getLocale();

    // contract fields
    $pos  = $contract->position;
    $req  = $pos?->requiredPosition;
    $dept = $contract->department;

    // Title shown in both languages (fallbacks)
    $positionTitle = $req?->title
        ?? $pos?->getTranslation('name', $loc)
        ?? $pos?->name
        ?? ('#'.$contract->position_id);

    // Dates — keep identical approach to page1
    $start   = $contract->start_date;
    $end     = $contract->end_date;
    $startStr= $start ? Carbon::parse($start)->format('d/m/Y') : '—';
    $endStr  = $end   ? Carbon::parse($end)->format('d/m/Y')   : '—';

    // Numbers
    $weeklyHours = (int) ($contract->weekly_hours ?? 40);
    $salary      = (float) ($contract->salary ?? 0);
    // Avoid intl dependency; mimic page1 minimalism
    $salaryFormatted = $salary ? number_format($salary, 0, '.', ',') : '—';

    // helpers (same as page1)
    $esc = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));
    $L   = fn (string $txt) => '<span class="h-label">'.$txt.'</span>';

    /* ========================
       Build EN/AR text blocks
       ======================== */

    // Whereas
    $EN_WHEREAS = 'whereas “ Christian Hope Center Aleppo / Head office ” has agreed to this volunteer according to the conditions outlined in this contract,';
    $AR_WHEREAS = 'وبما أن "مركز الرجاء المسيحي - حلب - الإدارة العامة" قد وافق على تطوعه بالشروط المنصوص عليها في هذا العقد،';

    // Intro
    $EN_INTRO = 'This contract between the two parties witnesses mutual acceptance of the following:';
    $AR_INTRO = 'تم العقد بين الطرفين والقبول المشترك لما يلي:';

    // Contract type
    $EN_TYPE = $L('Contract type:').' Volunteer agreement for limited period';
    $AR_TYPE = $L('نوع العقد:').' عقد تطوعي لمدة محدودة';

    // Place of work (static as per design)
    $EN_PLACE = $L('Place of Work:').' Aleppo – Christian Hope Center – Head Office';
    $AR_PLACE = $L('مكان العمل:').' حلب – مركز الرجاء المسيحي – المكتب';

    // Job description (dynamic title)
    $EN_JD = $L('Job description:').' '.$esc($positionTitle).',';
    $AR_JD = $L('وصف العمل:').' '.$esc($positionTitle).',';

    // Duration + dates
    $EN_DUR = $L('Duration of the agreement:').'<br>One Year<br><br>Starts from: '.$esc($startStr).'<br>And ends on: '.$esc($endStr);
    $AR_DUR = $L('مدة العقد:').'<br>سنة<br><br>تبدأ من تاريخ: '.$esc($startStr).'<br>وتنتهي في التاريخ: '.$esc($endStr);

    // Work hours
    $EN_HOURS = implode('<br>', [
        $L('Volunteer Work Hours:'),
        $weeklyHours.' working hours per week, 8 hours per day.',
        'Standard office hours are Monday to Friday from 9:30 AM to 5:30 PM.',
        '',
        'Saturdays are considered working days when necessary and not treated as extra hours.',
    ]);
    $AR_HOURS = implode('<br>', [
        $L('ساعات العمل التطوعي:'),
        $weeklyHours.' ساعة عمل أسبوعياً، 8 ساعات يومياً.',
        'الدوام الإداري من الاثنين إلى الجمعة من الساعة 9:30 AM حتى الساعة 5:30 PM.',
        '',
        'يُعتبر يوم السبت يوماً للعمل عند الحاجة، ولا يُعتبر دواماً إضافياً.',
    ]);

    // Stipend & benefits
    $EN_STIPEND = $L('Stipend and Benefits:').'<br>'
        .'Christian Hope Center is to give a monthly stipend of / '.$esc($salaryFormatted).' USD / or its equivalent in Syrian pounds, '
        .'to be paid in cash to the volunteer at the end of each month.';
    $AR_STIPEND = $L('المكافأة المالية والمزايا:').'<br>'
        .'يمنح مركز الرجاء المسيحي شهرياً للسيد/ة متطوع/ة مكافأة مقدارها / '.$esc($salaryFormatted).' / دولار أمريكي أو ما يعادلها بالليرة السورية، '
        .'تُمنح نقداً في نهاية كل شهر.';

    // Duties
    $EN_DUTIES = $L('Duties:').'<br>as listed in the job description of the position (attached).';
    $AR_DUTIES = $L('المسؤوليات:').'<br>حسب ما ورد في توصيف العمل التطوعي لهذا المنصب (مرفق).';
@endphp

{{-- Whereas --}}
<x-contract.dual tight :en="$EN_WHEREAS" :ar="$AR_WHEREAS"/>

{{-- Intro --}}
<x-contract.dual rowClass="mt-4" :en="$EN_INTRO" :ar="$AR_INTRO"/>

{{-- Contract type --}}
<x-contract.dual rowClass="mt-4" :en="$EN_TYPE" :ar="$AR_TYPE"/>

{{-- Place of Work --}}
<x-contract.dual rowClass="mt-2" :en="$EN_PLACE" :ar="$AR_PLACE"/>

{{-- Job description --}}
<x-contract.dual rowClass="mt-2" :en="$EN_JD" :ar="$AR_JD"/>

{{-- Duration + Dates --}}
<x-contract.dual rowClass="mt-4" :en="$EN_DUR" :ar="$AR_DUR"/>

{{-- Work hours --}}
<x-contract.dual rowClass="mt-4" :en="$EN_HOURS" :ar="$AR_HOURS"/>

{{-- Stipend & Benefits --}}
<x-contract.dual rowClass="mt-4" :en="$EN_STIPEND" :ar="$AR_STIPEND"/>

{{-- Duties --}}
<x-contract.dual rowClass="mt-4" :en="$EN_DUTIES" :ar="$AR_DUTIES"/>
