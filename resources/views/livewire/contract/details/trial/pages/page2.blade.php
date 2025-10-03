@php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $Digits = fn ($s) => $forPdf ? ar_digits((string)$s) : (string)$s;
    $L      = fn (string $t) => '<span class="h-label">'.$t.'</span>';

    // Dates
    $start = $contract->start_date ? Carbon::parse($contract->start_date) : null;
    $end   = $contract->end_date   ? Carbon::parse($contract->end_date)   : null;

    $startStr   = $start ? $start->format('d-m-Y') : '—';
    $endStr     = $end   ? $end->format('d-m-Y')   : '—';
    $startStrAr = $Digits($startStr);
    $endStrAr   = $Digits($endStr);

    $months = ($start && $end) ? max(1, $start->diffInMonths($end)) : 3;
    $durationEn = match($months){
        1 => 'One Month', 2 => 'Two Months', 3 => 'Three Months', 4 => 'Four Months',
        5 => 'Five Months', 6 => 'Six Months', default => $months . ' Months'
    };
    $durationAr = match($months){
        1 => 'شهر واحد', 2 => 'شهران', 3 => 'ثلاثة أشهر', 4 => 'أربعة أشهر',
        5 => 'خمسة أشهر', 6 => 'ستة أشهر', default => $Digits($months).' أشهر'
    };

    $position   = $contract->position;
    $program    = $position?->program->name ?? '—';
    $positionEN = $position?->title ?? '—';
    $positionAR = $position?->title_ar ?? '—';

    $placeEn = $program;
    $placeAr = $program;

    $esc   = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));
    $escAr = fn ($v) => e((string)($v === '' || $v === null ? '—' : $Glyphs($v)));
@endphp

<div class="avoid-break mt-4">
    <x-contract.dual :en="$L('Third term:')" :ar="$L($Glyphs('البند الثالث:'))"/>
</div>

{{-- 1 --}}
<x-contract.dual rowClass="mt-6 mb-10"
                 :en="'1. The contract period is '.$durationEn.' starting from '.$startStr.' until '.$endStr.' with a possibility to be extended in agreement between the two parties and according to the performance evaluation, under the condition of not exceeding six months period.'"
                 :ar="'1. مدة العقد '.$durationAr.' ابتداءً من '.$startStrAr.' وحتى '.$endStrAr.'، وهي قابلة للتمديد بالاتفاق بين الفريقين ووفقاً لتقييم الأداء، على أن لا تتجاوز مدة العقد ستة أشهر.'"
/>

{{-- 2 --}}
<x-contract.dual rowClass="mt-6 mb-10"
                 :en="'2. The trial period ends by confirming a permanent contract with the volunteer or dismissing his/her services according to a clear performance evaluation.'"
                 :ar="'2. تنتهي الفترة التجريبية بتثبيت المتطوع بعقد دائم أو الاعتذار عن الخدمات، وذلك وفق تقييم أداء واضح.'"
/>

{{-- 3 (dynamic position & program) --}}
<x-contract.dual rowClass="mt-6 mb-10"
                 :en="'3. The second party is committed to delivering volunteering services as a <strong>'.$esc($positionEN).'</strong> for the program “'.$esc($program).'” according to standard office hours; Monday to Friday from 9:30 AM to 5:30 PM.'"
                 :ar="$Glyphs('3. يلتزم الفريق الثاني بتقديم خدماته التطوعية بصفة <strong>'.$escAr($positionAR).'</strong> في برنامج '.$escAr($program).' وفق الدوام الإداري من الاثنين إلى الجمعة من الساعة 9:30 ص حتى 5:30 م.')"
/>

{{-- Place of Work --}}
<x-contract.dual rowClass="mt-2 mb-10"
                 :en="'Place of Work: '.e($placeEn)"
                 :ar="e($placeAr).$Glyphs(' : مكان العمل')"
/>

{{-- 4 --}}
<x-contract.dual rowClass="mt-6 mb-10"
                 :en="'4. Saturdays are considered working days when necessary and not treated as extra hours.'"
                 :ar="'4. يُعتبر يوم السبت يوم عمل في حال رأت الإدارة الحاجة لذلك ولا يعتبر ذلك دوام إضافي.'"
/>

{{-- 5 --}}
<x-contract.dual rowClass="mt-6 mb-10"
                 :en="'5. The second party (the volunteer) is committed to delivering all equipment, materials, and documents he/she received immediately to the Hope Center after the termination of this contract according to the receive list of every volunteer, which is regularly updated.'"
                 :ar="'5. يلتزم الفريق الثاني (المتطوع) عند انتهاء أو إنهاء الفترة التجريبية بتسليم جميع التجهيزات والمواد والوثائق المؤتمن بها لمركز الرجاء، وذلك استناداً لقائمة الاستلام الشخصي لكل متطوع والتي يتم تحديثها دورياً.'"
/>
