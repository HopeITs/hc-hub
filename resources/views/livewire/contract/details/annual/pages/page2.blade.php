@php
    use Illuminate\Support\Str;

    $loc      = $locale ?? app()->getLocale();

    $positionTitle = $contract->position?->requiredPosition?->title
                  ?? $contract->position?->name
                  ?? ('#'.$contract->position_id);

    $startDate = optional($contract->start_date)->format('d/m/Y') ?: '—';
    $endDate   = optional($contract->end_date)->format('d/m/Y') ?: '—';

    $weeklyHours = (int) ($contract->weekly_hours ?? 40);
    $salary      = (float) ($contract->salary ?? 0);
    $salaryFormatted = number_format($salary, 0);

    // tiny helper for safe HTML text where needed
    $esc = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));

    // Pre-build red labels to match page1
    $L = fn(string $txt) => '<span class="h-label">'.$txt.'</span>';
@endphp

{{-- Whereas --}}
<x-contract.dual tight
                 :en="'whereas “ Christian Hope Center Aleppo / Head office ” has agreed to this volunteer according to the conditions outlined in this contract,'"
                 :ar="'وبما أن \"مركز الرجاء المسيحي - حلب - الإدارة العامة\" قد وافق على تطوعه بالشروط المنصوص عليها في هذا العقد،'"
/>

{{-- Intro line --}}
<x-contract.dual rowClass="mt-4"
                 :en="'This contract between the two parties witnesses mutual acceptance of the following:'"
                 :ar="'تم العقد بين الطرفين والقبول المشترك لما يلي:'"
/>

{{-- Contract type --}}
<x-contract.dual rowClass="mt-4"
                 :en="$L('Contract type:').' Volunteer agreement for limited period'"
                 :ar="$L('نوع العقد:').' عقد تطوعي لمدة محدودة'"
/>

{{-- Place of Work --}}
<x-contract.dual rowClass="mt-2"
                 :en="$L('Place of Work:').' Aleppo – Christian Hope Center – Head Office'"
                 :ar="$L('مكان العمل:').' حلب – مركز الرجاء المسيحي – المكتب'"
/>

{{-- Job description --}}
<x-contract.dual rowClass="mt-2"
                 :en="$L('Job description:').' '.$esc($positionTitle).','"
                 :ar="$L('وصف العمل:').' '.$esc($positionTitle).','"
/>

{{-- Duration + dates --}}
<x-contract.dual rowClass="mt-4"
                 :en="$L('Duration of the agreement:').'<br>One Year<br><br>Starts from: '.$esc($startDate).'<br>And ends on: '.$esc($endDate)"
                 :ar="$L('مدة العقد:').'<br>سنة<br><br>تبدأ من تاريخ: '.$esc($startDate).'<br>وتنتهي في التاريخ: '.$esc($endDate)"
/>

{{-- Work hours --}}
@php
    $EN_HOURS = implode('<br>', [
        $L('Volunteer Work Hours:'),
        $weeklyHours.' working hours per week, 8 hours per day.',
        'Standard office hours are Monday to Friday from 9:30 AM to 5:30 PM.',
        '',
        'Saturdays are considered working days when necessary and not treated as extra hours.'
    ]);

    $AR_HOURS = implode('<br>', [
        $L('ساعات العمل التطوعي:'),
        $weeklyHours.' ساعة عمل أسبوعياً، 8 ساعات يومياً.',
        'الدوام الإداري من الاثنين إلى الجمعة من الساعة 9:30 AM حتى الساعة 5:30 PM.',
        '',
        'يُعتبر يوم السبت يوماً للعمل عند الحاجة، ولا يُعتبر دواماً إضافياً.'
    ]);
@endphp

<x-contract.dual rowClass="mt-4" :en="$EN_HOURS" :ar="$AR_HOURS"/>

{{-- Stipend & Benefits --}}
@php
    $EN_STIPEND = $L('Stipend and Benefits:').'<br>'.
        'Christian Hope Center is to give a monthly stipend of / '.$salaryFormatted.' USD / or its equivalent in Syrian pounds, '.
        'to be paid in cash to the volunteer at the end of each month.';

    $AR_STIPEND = $L('المكافأة المالية والمزايا:').'<br>'.
        'يمنح مركز الرجاء المسيحي شهرياً للسيد/ة متطوع/ة مكافأة مقدارها / '.$salaryFormatted.' / دولار أمريكي أو ما يعادلها بالليرة السورية، '.
        'تُمنح نقداً في نهاية كل شهر.';
@endphp

<x-contract.dual rowClass="mt-4" :en="$EN_STIPEND" :ar="$AR_STIPEND"/>

{{-- Duties --}}
<x-contract.dual rowClass="mt-4"
                 :en="$L('Duties:').'<br>as listed in the job description of the position (attached).'"
                 :ar="$L('المسؤوليات:').'<br>حسب ما ورد في توصيف العمل التطوعي لهذا المنصب (مرفق).'"
/>
