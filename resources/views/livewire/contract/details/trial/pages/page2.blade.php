@php
    use Illuminate\Support\Carbon;

    // Dates
    $start = $contract->start_date ? Carbon::parse($contract->start_date) : null;
    $end   = $contract->end_date   ? Carbon::parse($contract->end_date)   : null;

    $startStr = $start ? $start->format('d-m-Y') : '—';
    $endStr   = $end   ? $end->format('d-m-Y')   : '—';

    $months = ($start && $end) ? max(1, $start->diffInMonths($end)) : 3;
    $durationEn = match($months){
        1 => 'One Month', 2 => 'Two Months', 3 => 'Three Months', 4 => 'Four Months',
        5 => 'Five Months', 6 => 'Six Months', default => $months . ' Months'
    };
    $durationAr = match($months){
        1 => 'شهر واحد', 2 => 'شهران', 3 => 'ثلاثة أشهر', 4 => 'أربعة أشهر',
        5 => 'خمسة أشهر', 6 => 'ستة أشهر', default => $months . ' أشهر'
    };

    $placeEn = 'Aleppo – Christian Hope Center – Head Office';
    $placeAr = 'مكان العمل: حلب – مركز الرجاء المسيحي – المكتب الرئيسي';

    $EN_THIRD_TERM_LABEL = '<span class="h-label">Third term:</span>';
    $AR_THIRD_TERM_LABEL = '<span class="h-label">البند الثالث:</span>';

    // List items (EN)
    $EN_LI_1 = "The contract period is {$durationEn} starting from {$startStr} until {$endStr} with a possibility to be extended in agreement between the two parties and according to the performance evaluation, under the condition of not exceeding six months period.";
    $EN_LI_2 = 'The trial period ends by confirming a permanent contract with the volunteer or dismissing his/her services according to a clear performance evaluation.';
    $EN_LI_3 = 'The second party is committed to delivering volunteering services as a MEAL Officer according to standard office hours; Monday to Friday from 9:30 AM to 5:30 PM.<br><br><em>Place of Work:</em> ' . e($placeEn) . '.';
    $EN_LI_4 = 'Saturdays are considered working days when necessary and not treated as extra hours.';
    $EN_LI_5 = 'The second party (the volunteer) is committed to delivering all equipment, materials, and documents he/she received immediately to the Hope Center after the termination of this contract according to the receive list of every volunteer, which is regularly updated.';

    $AR_LI_1 = "مدة العقد {$durationAr} ابتداءً من {$startStr} وحتى {$endStr}، مع إمكانية التمديد باتفاق الطرفين ووفقاً لتقييم الأداء، على أن لا تتجاوز المدة ستة أشهر.";
    $AR_LI_2 = 'تنتهي الفترة التجريبية بتثبيت المتطوع بعقد دائم أو الاعتذار عن الخدمات، وذلك وفق تقييم أداء واضح.';
    $AR_LI_3 = 'يلتزم الفريق الثاني بتقديم خدماته التطوعية كمسؤول متابعة وتقييم ومساءلة وتعلم وفق الدوام الإداري من الاثنين إلى الجمعة من الساعة 9:30 ص حتى 5:30 م.<br><br>' . e($placeAr) . '.';
    $AR_LI_4 = 'يُعتبر يوم السبت يوماً عملياً عند الضرورة ولا يُحتسب كدوام إضافي.';
    $AR_LI_5 = 'يلتزم الفريق الثاني (المتطوع) عند انتهاء الفترة التجريبية بتسليم جميع التجهيزات والمواد والوثائق التي استلمها فوراً إلى مركز الرجاء، وذلك استناداً إلى لائحة الاستلام الخاصة بكل متطوع والتي يتم تحديثها دورياً.';
@endphp

<x-contract.dual rowClass="mt-1" :en="$EN_THIRD_TERM_LABEL" :ar="$AR_THIRD_TERM_LABEL"/>

<div class="dual-row mt-2">
    <div class="dual-col dual-en">
        <ol class="list-decimal pl-5 space-y-3">
            <li>{!! $EN_LI_1 !!}</li>
            <li>{!! $EN_LI_2 !!}</li>
            <li>{!! $EN_LI_3 !!}</li>
            <li>{!! $EN_LI_4 !!}</li>
            <li>{!! $EN_LI_5 !!}</li>
        </ol>
    </div>

    <div class="dual-col dual-ar">
        <ol class="list-decimal pr-5 space-y-3" style="direction: rtl; text-align: right;">
            <li>{!! $AR_LI_1 !!}</li>
            <li>{!! $AR_LI_2 !!}</li>
            <li>{!! $AR_LI_3 !!}</li>
            <li>{!! $AR_LI_4 !!}</li>
            <li>{!! $AR_LI_5 !!}</li>
        </ol>
    </div>
</div>
