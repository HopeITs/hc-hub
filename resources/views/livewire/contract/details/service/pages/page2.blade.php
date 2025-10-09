@php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $Digits = fn ($s) => $forPdf ? ar_digits((string)$s) : (string)$s;
    $L      = fn (string $t) => '<span class="h-label">'.$t.'</span>';

    // Dates
    $start   = $contract->start_date ? Carbon::parse($contract->start_date) : null;
    $end     = $contract->end_date   ? Carbon::parse($contract->end_date)   : null;

    $startStr   = $start ? $start->format('d-m-Y') : '—';
    $endStr     = $end   ? $end->format('d-m-Y')   : '—';
    $startStrAr = $start ? $Digits($start->format('Y/m/d')) : '—';
    $endStrAr   = $end   ? $Digits($end->format('Y/m/d'))   : '—';

    $center = $contract->position?->center?->name ??  '—';


@endphp

{{-- First term --}}
<div class="avoid-break mt-4">
    <x-contract.dual :en="$L('First term:')" :ar="$L($Glyphs('البند الأول:'))"/>
</div>
<x-contract.dual rowClass="mt-2 mb-6"
                 :en="'The introduction of this contract is considered an indissoluble part of it.'"
                 :ar="$Glyphs('تُعتبر مقدمة هذا العقد جزءاً لا يتجزأ من هذا العقد.')"/>

{{-- Second term --}}
<div class="avoid-break mt-4">
    <x-contract.dual :en="$L('Second term:')" :ar="$L($Glyphs('البند الثاني:'))"/>
</div>
<x-contract.dual rowClass="mt-2 mb-6"
                 :en="'The second party is committed to implementing all volunteering activities agreed on with the first party and all requested programming services.'"
                 :ar="$Glyphs('على الفريق الثاني الالتزام بالقيام بكافة الأعمال التطوعية المتفق عليها مع الفريق الأول وكافة ما يطلب منه من خدمات.')"/>

{{-- Third term --}}
<div class="avoid-break mt-4">
    <x-contract.dual :en="$L('Third term:')" :ar="$L($Glyphs('البند الثالث:'))"/>
</div>

{{-- 1 --}}
<x-contract.dual rowClass="mt-2 mb-6"
                 :en="'1. The contract is considered valid from '.$startStr.' until '.$endStr.' and the first two months are considered a trial period for the second party where the first party is entitled to terminate the contract and not continue with the second party.'"
                 :ar="'1. تبدأ مدة هذا العقد  <br> اعتباراً من '.$startStrAr.' <br>  وتنتهي في '.$endStrAr.' <Br> ويُعتبر أول شهرين مدةً تجريبية للفريق الثاني يحق فيها للفريق الأول إنهاء العقد وعدم الاستمرار مع الفريق الثاني.'"/>

{{-- 2 --}}
<x-contract.dual rowClass="mt-2 mb-6"
                 :en="'This contract will not be renewed without a written agreement of both parties, whereas each party shall inform the other of the contract termination one month prior to the end of the contract.'"
                 :ar="$Glyphs('لا يتُجدّد هذا العقد لمدة أخرى إلا بموافقة الفريقين خطياً، على أن يُعلم كل فريق  الآخر بعدم التمديد قبل شهر من انتهاء العقد.')"/>

{{-- 3 --}}
<x-contract.dual rowClass="mt-2 mb-6"
                 :en="'Place of Work: '.$center"
                 :ar="$center.' :مكان العمل'"/>
