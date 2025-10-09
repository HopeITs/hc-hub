@php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;
    $Glyphs = fn($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $L = fn($t) => '<span class="h-label">'.$t.'</span>';
    $esc = fn($v) => e($v === '' || $v === null ? '—' : $v);
    $escAr = fn($v) => e($v === '' || $v === null ? '—' : $Glyphs($v));

    $dailyStart = '9:30 AM';
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
        $Glyphs(__('general.text41',['position'=>$positionAR,'program'=>$program])).'<br>'.$Glyphs(__('general.text24')).'<br>'.$dailyEnd.' '.$Glyphs(__('general.text26')).' '.$dailyStart.$Glyphs(__('general.text25')).'<br>'.$placeAr.$Glyphs(' : مكان العمل'),
        $Glyphs(__('general.text42')),
        $Glyphs(__('general.text43')),
    ];
@endphp

<div class="avoid-break mt-4">
    <x-contract.dual :en="$L('Third term:')" :ar="$L($Glyphs('البند الثالث:'))"/>
</div>

<x-contract.dual rowClass="mt-6"
                 :en="$asList($EN_LIST_3,'en',1)"
                 :ar="$asList($AR_LIST_3,'ar',1)"/>
