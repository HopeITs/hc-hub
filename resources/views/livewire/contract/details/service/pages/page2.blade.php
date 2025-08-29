@php
    use Illuminate\Support\Carbon;

    $start   = $contract->start_date ? Carbon::parse($contract->start_date) : null;
    $end     = $contract->end_date   ? Carbon::parse($contract->end_date)   : null;

    $startEn = $start ? $start->format('d-m-Y') : '—';
    $endEn   = $end   ? $end->format('d-m-Y')   : '—';
    $startAr = $start ? $start->format('Y/m/d') : '—';
    $endAr   = $end   ? $end->format('Y/m/d')   : '—';

    $EN_FIRST_TERM_LABEL  = '<span class="h-label">First term:</span>';
    $AR_FIRST_TERM_LABEL  = '<span class="h-label">البند الأول:</span>';

    $EN_SECOND_TERM_LABEL = '<span class="h-label">Second term:</span>';
    $AR_SECOND_TERM_LABEL = '<span class="h-label">البند الثاني:</span>';

    $EN_THIRD_TERM_LABEL  = '<span class="h-label">Third term:</span>';
    $AR_THIRD_TERM_LABEL  = '<span class="h-label">البند الثالث:</span>';

    $EN_FIRST_TERM_TEXT = 'The introduction of this contract is considered an indissoluble part of it.';
    $AR_FIRST_TERM_TEXT = 'تُعتبر مقدمة هذا العقد جزءاً لا يتجزأ من هذا العقد.';

    $EN_SECOND_TERM_TEXT = 'The second party is committed to implementing all volunteering activities agreed on with the first party and all requested programming services.';
    $AR_SECOND_TERM_TEXT = 'على الفريق الثاني الالتزام بالقيام بكافة الأعمال التطوعية المتفق عليها مع الفريق الأول وجميع الخدمات البرمجية المطلوبة.';

    $EN_THIRD_LI_1 = "The contract is considered valid from {$startEn} until {$endEn} and the first two months are considered a trial period for the second party where the first party is entitled to terminate the contract and not continue with the second party.";
    $AR_THIRD_LI_1 = "تُعد مدة هذا العقد اعتباراً من {$startAr} وتنتهي في {$endAr} ويُعتبر أول شهرين مدةً تجريبية للفريق الثاني يحق فيها للفريق الأول إنهاء العقد وعدم الاستمرار مع الفريق الثاني.";

    $EN_NO_RENEWAL = 'This contract will not be renewed without a written agreement of both parties, whereas each party shall inform the other of the contract termination one month prior to the end of the contract.';
    $AR_NO_RENEWAL = 'لا يُجدّد هذا العقد إلا بموافقة خطيّة من الطرفين، على أن يُعلم كل طرف الطرف الآخر بقرار إنهاء العقد قبل انتهاء مدته بشهر واحد.';

    $EN_PLACE = 'Place of Work: Aleppo – Christian Hope Center – Head Office';
    $AR_PLACE = 'مكان العمل: حلب – مركز الرجاء المسيحي – المكتب الرئيسي';
@endphp

<x-contract.dual rowClass="mt-1" :en="$EN_FIRST_TERM_LABEL" :ar="$AR_FIRST_TERM_LABEL"/>
<x-contract.dual :en="$EN_FIRST_TERM_TEXT" :ar="$AR_FIRST_TERM_TEXT"/>

<x-contract.dual rowClass="mt-4" :en="$EN_SECOND_TERM_LABEL" :ar="$AR_SECOND_TERM_LABEL"/>
<x-contract.dual :en="$EN_SECOND_TERM_TEXT" :ar="$AR_SECOND_TERM_TEXT"/>

<x-contract.dual rowClass="mt-4" :en="$EN_THIRD_TERM_LABEL" :ar="$AR_THIRD_TERM_LABEL"/>

<div class="dual-row mt-2">
    <div class="dual-col dual-en">
        <ol class="list-decimal pl-5 space-y-3">
            <li>{!! $EN_THIRD_LI_1 !!}</li>
        </ol>
    </div>
    <div class="dual-col dual-ar">
        <ol class="list-decimal pr-5 space-y-3" style="direction: rtl; text-align: right;">
            <li>{!! $AR_THIRD_LI_1 !!}</li>
        </ol>
    </div>
</div>

<x-contract.dual rowClass="mt-4" :en="$EN_NO_RENEWAL" :ar="$AR_NO_RENEWAL"/>
<x-contract.dual rowClass="mt-2" :en="$EN_PLACE" :ar="$AR_PLACE"/>
