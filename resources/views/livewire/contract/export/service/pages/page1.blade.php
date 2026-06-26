{{-- @php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string) $s) : (string) $s;
    $Digits = fn ($s) => $forPdf ? ar_digits((string) $s) : (string) $s;

    $emp = $contract->employee;
    $position = $contract->position;

    $positionEN = $position?->title ?? '—';
    $positionAR = $position?->title_ar ?? '—';

    // Fields
    $nameEn =  $emp?->name_en ?? '—';
    $nameAr =  $Glyphs($emp?->name_ar ??  '—');
    $pobEn = trim((string) ($emp?->pob_en ?? '—'));
    $pobAr = trim((string) ($Glyphs($emp?->pob_ar ?? '—')));

    $dob = $emp?->dob;
    $dobStr   = $dob ? Carbon::parse($dob)->format('d/m/Y') : '—';
    $dobStrAr = $Digits($dobStr);

    $nationalId = $emp?->national_number;

    $nationalityRaw = (string) ($emp?->nationality ?? '');
    $natKey = strtoupper(trim($nationalityRaw));
    $NATIONALITY_MAP = [
        '1'   => ['en' => 'Syrian', 'ar' => 'سوري'],
        'SY'  => ['en' => 'Syrian', 'ar' => 'سوري'],
        'SYR' => ['en' => 'Syrian', 'ar' => 'سوري'],
    ];
    $natEn = $NATIONALITY_MAP[$natKey]['en'] ?? ($nationalityRaw ?: '—');
    $natAr = $Glyphs($NATIONALITY_MAP[$natKey]['ar'] ?? ($nationalityRaw ?: '—'));

    $addressEn = $emp->address_en ?? '';

    $addressAr = $Glyphs($emp->address_ar ?? '');

    $esc   = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));
    $escAr = fn ($v) => e((string)($v === '' || $v === null ? '—' : $Glyphs($v)));

    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';
@endphp

<div style="position: absolute ; top: 85px ;  left:335px ; right: 0 ; font-size: 18px ;">
    <x-contract.dual
            :en="$L($Glyphs('عقد اتفاق تطوعي'))"
            enClass="text-base"/>
</div>
<br>
<div class="avoid-break mt-6 " style="line-height: 0.9">
    <x-contract.dual tight
                     :en="$L('PRIVATE AND CONFIDENTIAL')"
                     :ar="$L($Glyphs('سري وخاص'))"
                     enClass="text-base" arClass="text-base"
    />
    <x-contract.dual tight
                     :en="' Volunteer agreement for a limited period'"
                     :ar="$Glyphs('عقد تطوعي لمدة محدودة')"
                     enClass="text-base" arClass="text-base"
    />
</div>


<div class="avoid-break " style="line-height: 1">
    <x-contract.dual rowClass=" fw-bold" :en="'Between'" :ar="$Glyphs('بين')"/>
    <x-contract.dual rowClass="mt-1" :en="$L('First Party:')" :ar="$L($Glyphs('الفريق الأول:'))"/>
    <div>
        <x-contract.dual
                :en="'Christian Hope Center - Aleppo / Head office <br><strong> represented in this Contract by Mr. Fredy Youssef</strong> <br><br>'"
                :ar="$Glyphs(__('general.text1'))"
        />
    </div>
    <div style="line-height: 0.9 ">
        <x-contract.dual
                :en="'Mr. Fredy Youssef is delegated by the <br>Latin church  in Syria to manage The Christian<br> Hope Center  and all affiliated programs.'"
                :ar="$Glyphs(__('general.text2'))"
        />
    </div>
    <div class="mt-4">
        <x-contract.dual
                :en="'Hereinafter referred to as “<strong>Christian Hope <br>  Center - Aleppo - Head Office</strong>”'"
                :ar="$Glyphs(__('general.text3'))"
        />
    </div>
</div>


<div class="avoid-break mt-2" style="line-height: 0.9">
    <x-contract.dual rowClass="mt-3 fw-bold" :en="'And'" :ar="$Glyphs('و')"/>
    <x-contract.dual rowClass="mt-1" :en="$L('Second Party:')" :ar="$L($Glyphs('الفريق الثاني:'))"/>

    <div>
        <x-contract.dual
                :en="'<strong>Ms./Mrs.</strong> '.$esc($nameEn)"
                :ar="$esc($nameAr).' '.$Glyphs(__('general.text4'))"/>
        <x-contract.dual
                :en="'<strong>Date and Place of Birth:</strong> '.$esc($pobEn).' — '.$esc($dobStr)"
                :ar="$esc($dobStrAr).' — '.$esc($pobAr).$Glyphs(__('general.text5'))"/>
        <x-contract.dual
                :en="'<strong>National ID number:</strong> '.$esc($nationalId)"
                :ar="$esc(ar_digits($nationalId ?? '')).' '.$Glyphs(__('general.text6'))"/>
        <x-contract.dual
                :en="'<strong>Nationality:</strong> '.$esc($natEn)"
                :ar="$esc($natAr).' '.$Glyphs(__('general.text7'))"/>
        <x-contract.dual
                :en="'<strong>Address:</strong> '.$esc($addressEn)"
                :ar="$esc($addressAr).' '.$Glyphs(__('general.text8'))"/>
    </div>
</div>


<div class="avoid-break mt-2" style="line-height: 0.9">
    <x-contract.dual :en="$L('Contract Introduction:')" :ar="$L($Glyphs('مقدمة العقد:'))"/>
    <x-contract.dual
            :en="'The First Party wants to utilize the expertise of the second party as a  <strong>'.$esc($positionEN).'</strong> and offered the second party this position whereas the second party has agreed to this offer.'"
            :ar="$Glyphs(__('general.text36', ['position' => $positionAR]))"
    />
</div> --}}









{{-- @php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    // Functions to handle Arabic rendering and digits specifically for DomPDF
    $Glyphs = fn($s) => $forPdf ? AR::glyphs((string) $s) : (string) $s;
    $Digits = fn($s) => $forPdf ? ar_digits((string) $s) : (string) $s;

    $loc = $locale ?? app()->getLocale();
    $emp = $contract->employee;
    $position = $contract->position;
    $program = $position?->program;

    // Name Construction
    $nameEn = trim(($emp?->first_name_en ?? '') . ' ' . ($emp?->father_name_en ?? '') . ' ' . ($emp?->last_name_en ?? ''));
    $nameEn = $nameEn !== '' ? $nameEn : '—';

    $nameAr = trim(($emp?->first_name_ar ?? '') . ' ' . ($emp?->father_name_ar ?? '') . ' ' . ($emp?->last_name_ar ?? ''));
    $nameAr = $nameAr !== '' ? $nameAr : '—';

    // Location Data
    $addressEn = $emp?->address_en ?? '—';
    $addressAr = $emp?->address_ar ?? '—';

    // Dates
    $dob = $emp?->dob;
    $dobStr = $dob ? Carbon::parse($dob)->format('d/m/Y') : '—';
    $dobStrAr = $Digits($dobStr);

    $pobEn = trim((string) ($emp?->pob_en ?? '—'));
    $pobAr = trim((string) ($emp?->pob_ar ?? '—'));
    $nationalId = $emp?->national_number;

    // Nationality Mapping
    $nationalityRaw = (string) ($emp?->nationality ?? '');
    $natKey = strtoupper(trim($nationalityRaw));
    $NATIONALITY_MAP = [
        '1' => ['en' => 'Syrian', 'ar' => 'سوري'],
        'SY' => ['en' => 'Syrian', 'ar' => 'سوري'],
        'SYR' => ['en' => 'Syrian', 'ar' => 'سوري'],
    ];
    $natEn = $NATIONALITY_MAP[$natKey]['en'] ?? ($nationalityRaw ?: '—');
    $natAr = $Glyphs($NATIONALITY_MAP[$natKey]['ar'] ?? ($nationalityRaw ?: '—'));

    // Position Details
    $req = $position?->requiredPosition;
    $positionEN = $req?->title_en ?? ($position?->title_en ?? '—');
    $positionAR = $req?->title_ar ?? ($position?->title_ar ?? '—');
    
    $centerNameEn = $position?->center?->name_en ?? $position?->center?->name ?? '—';
    $centerNameAr = $position?->center?->name_ar ?? $position?->center?->name ?? '—';
    $cityNameEn = $position?->city?->name_en ?? '—';
    $cityNameAr = $position?->city?->name_ar ?? '—';

    $workPlaceEn = $cityNameEn . ' - ' . $centerNameEn;
    $workPlaceAr = $cityNameAr . ' - ' . $centerNameAr;

    // Escaping helpers
    $esc = fn($v) => e((string) ($v === '' || $v === null ? '—' : $v));
    $escAr = fn($v) => e((string) ($v === '' || $v === null ? '—' : $Glyphs($v)));

    // Label helpers 
    $L = fn(string $t) => '<span class="h-label">' . $t . '</span>';
    $LB = fn(string $t) => '<span class="h-label-bold">' . $t . '</span>';
@endphp

<div style="padding-left: 40px; padding-right: 40px;">

    <div class="avoid-break contract-title-center">
        <div class="dual-row dual-tight">
            <div class="dual-en">
                Voluntary Service Contract
            </div>
            <div class="contract-title-separator">|</div>
            <div class="dual-ar">
                {{ $Glyphs('عقد تقديم خدمة تطوعيّّة محددة') }}
            </div>
        </div>
    </div>

    <div class="avoid-break mt-4">
        <x-contract.dual rowClass="fw-bold" :en="'Between'" :ar="$Glyphs('بين')" />
        <x-contract.dual rowClass="mt-1" :en="$L('First Party:')" :ar="$L($Glyphs('الفريق الأول:'))" />
        <x-contract.dual 
            :en="'<strong>Christian Hope Center - Syria</strong><br>Represented by the Vice chairman of the board of trustees<br><strong>Mr. Fredy Youssef</strong>'"
            :ar="$Glyphs('<strong>مؤسسة مركز الرجاء المسيحي - سوريا</strong><br>ينوب عنها بالتوقيع نائب رئيس مجلس الأمناء<br><strong>السيد فريدي يوسف</strong>')" />
    </div>

    <div class="avoid-break mt-1">
        <x-contract.dual rowClass="mt-1 fw-bold" :en="'And'" :ar="$Glyphs('و')" />
        <x-contract.dual rowClass="mt-1" :en="$L('Second Party:')" :ar="$L($Glyphs('الفريق الثاني:'))" />
        
        <x-contract.dual 
            :en="'Ms./Mrs.: <strong>' . $esc($nameEn) . '</strong>'" 
            :ar="$Glyphs('الاسم الثلاثي:').' <strong>'.$escAr($nameAr).'</strong>'" />

        <x-contract.dual 
            :en="'Date and Place of Birth: <strong>' . $esc($pobEn) . ' — ' . $esc($dobStr) . '</strong>'"
            :ar="$Glyphs('مكان وتاريخ الولادة:').' <strong>'.$escAr($pobAr).' — '.$esc($dobStrAr).'</strong>'" />

        <x-contract.dual 
            rowClass="mt-3" 
            :en="'National ID number: <strong>' . $esc($nationalId) . '</strong>'"
            :ar="$Glyphs('الرقم الوطني:').' <strong>'.$esc($Digits($nationalId ?? '')).'</strong>'" />

        <x-contract.dual 
            :en="'Nationality: <strong>' . $esc($natEn) . '</strong>'" 
            :ar="$Glyphs('الجنسية:').' <strong>'.$escAr($natAr).'</strong>'" />

        <x-contract.dual 
            :en="'Address: <strong>' . $esc($addressEn) . '</strong>'" 
            :ar="$Glyphs('العنوان:').' <strong>'.$escAr($addressAr).'</strong>'" />
    </div>

    <div class="avoid-break mt-4">
        <x-contract.dual rowClass="fw-bold" :en="'Preamble:'" :ar="$Glyphs('مقدمة العقد:')" />
        <x-contract.dual 
            style="margin-top:-3px" 
            :en="'Since the first party oversees the implementation of programs in Syria and the nature of the program requires a volunteer in the capacity of <strong>' . $esc($positionEN) . '</strong>'"
            :ar="$Glyphs('لما كان الفريق الأول يشرف على تنفيذ البرامج في سوريا، وطبيعة البرنامج تحتاج إلى متطوع بصفة ').'<strong>'.$escAr($positionAR).'</strong>'" />

        <x-contract.dual 
            :en="'to carry out the tasks mentioned in the attached job description,'" 
            :ar="$Glyphs('لتنفيذ المهام المذكورة في التوصيف الوظيفي المرفق،')" />

        <x-contract.dual 
            :en="'And since the second team has shown willingness to undertake this voluntary work starting from the date of signing this contract,'" 
            :ar="$Glyphs('ولما كان الفريق الثاني قد أبدى استعداده للقيام بهذا العمل التطوعي ابتداءً من تاريخ توقيع هذا العقد،')" />

        <x-contract.dual 
            :en="'The contract has been made between the two parties with mutual agreement of the following:'" 
            :ar="$Glyphs('تم العقد بين الطرفين والقبول المشترك لما يلي:')" />
    </div>

    <div class="avoid-break mt-3">
        <x-contract.dual 
            :en="$LB('1. Contract Preamble:') . ' an integral part of it.'" 
            :ar="$LB($Glyphs('1. مقدمة العقد:')) . ' ' . $Glyphs('جزء لا يتجزأ منه.')" />

        <x-contract.dual 
            :en="$LB('2. Contract type:') . ' <strong>Voluntary Service Contract.</strong>'"
            :ar="$LB($Glyphs('2. نوع العقد:')). ' <strong>'.$Glyphs('عقد تقديم خدمة تطوعيّّة محددة.').'</strong>'" />

        <x-contract.dual 
            :en="$LB('3. Place of Work:') . ' <strong>' . $esc($workPlaceEn) . '</strong>'"
            :ar="$LB($Glyphs('3. مكان العمل:')). ' <strong>'.$escAr($workPlaceAr).'</strong>'" />
    </div>

</div> --}}






{{-- @php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    // Functions to handle Arabic rendering and digits specifically for DomPDF
    $Glyphs = fn($s) => $forPdf ? AR::glyphs((string) $s) : (string) $s;
    $Digits = fn($s) => $forPdf ? ar_digits((string) $s) : (string) $s;

    $loc = $locale ?? app()->getLocale();
    $emp = $contract->employee;
    $position = $contract->position;
    $program = $position?->program;

    // Name Construction
    $nameEn = trim(($emp?->first_name_en ?? '') . ' ' . ($emp?->father_name_en ?? '') . ' ' . ($emp?->last_name_en ?? ''));
    $nameEn = $nameEn !== '' ? $nameEn : '—';

    $nameAr = trim(($emp?->first_name_ar ?? '') . ' ' . ($emp?->father_name_ar ?? '') . ' ' . ($emp?->last_name_ar ?? ''));
    $nameAr = $nameAr !== '' ? $nameAr : '—';

    // Location Data
    $addressEn = $emp?->address_en ?? '—';
    $addressAr = $emp?->address_ar ?? '—';

    // Dates
    $dob = $emp?->dob;
    $dobStr = $dob ? Carbon::parse($dob)->format('d/m/Y') : '—';
    $dobStrAr = $Digits($dobStr);

    $pobEn = trim((string) ($emp?->pob_en ?? '—'));
    $pobAr = trim((string) ($emp?->pob_ar ?? '—'));
    $nationalId = $emp?->national_number;

    // Nationality Mapping
    $nationalityRaw = (string) ($emp?->nationality ?? '');
    $natKey = strtoupper(trim($nationalityRaw));
    $NATIONALITY_MAP = [
        '1' => ['en' => 'Syrian', 'ar' => 'سوري'],
        'SY' => ['en' => 'Syrian', 'ar' => 'سوري'],
        'SYR' => ['en' => 'Syrian', 'ar' => 'سوري'],
    ];
    $natEn = $NATIONALITY_MAP[$natKey]['en'] ?? ($nationalityRaw ?: '—');
    $natAr = $Glyphs($NATIONALITY_MAP[$natKey]['ar'] ?? ($nationalityRaw ?: '—'));

    // Position Details
    $req = $position?->requiredPosition;
    $positionEN = $req?->title_en ?? ($position?->title_en ?? '—');
    $positionAR = $req?->title_ar ?? ($position?->title_ar ?? '—');
    
    $centerNameEn = $position?->center?->name_en ?? $position?->center?->name ?? '—';
    $centerNameAr = $position?->center?->name_ar ?? $position?->center?->name ?? '—';
    $cityNameEn = $position?->city?->name_en ?? '—';
    $cityNameAr = $position?->city?->name_ar ?? '—';

    $workPlaceEn = $cityNameEn . ' - ' . $centerNameEn;
    $workPlaceAr = $cityNameAr . ' - ' . $centerNameAr;

    // Escaping helpers
    $esc = fn($v) => e((string) ($v === '' || $v === null ? '—' : $v));
    $escAr = fn($v) => e((string) ($v === '' || $v === null ? '—' : $Glyphs($v)));

    // Label helpers 
    $L = fn(string $t) => '<span class="h-label">' . $t . '</span>';
    $LB = fn(string $t) => '<span class="h-label-bold">' . $t . '</span>';
@endphp

<div style="padding-top: 90px; padding-left: 40px; padding-right: 40px;">

    <div class="avoid-break contract-title-center">
        <div class="dual-row dual-tight">
            <div class="dual-en">
                Voluntary Service Contract
            </div>
            <div class="contract-title-separator">|</div>
            <div class="dual-ar">
                {{ $Glyphs('عقد تقديم خدمة تطوعيّّة محددة') }}
            </div>
        </div>
    </div>

    <div class="avoid-break mt-4">
        <x-contract.dual rowClass="fw-bold" :en="'Between'" :ar="$Glyphs('بين')" />
        <x-contract.dual rowClass="mt-1" :en="$L('First Party:')" :ar="$L($Glyphs('الفريق الأول:'))" />
        <x-contract.dual 
            :en="'<strong>Christian Hope Center - Syria</strong><br>Represented by the Vice chairman of the board of trustees<br><strong>Mr. Fredy Youssef</strong>'"
            :ar="$Glyphs('<strong>مؤسسة مركز الرجاء المسيحي - سوريا</strong><br>ينوب عنها بالتوقيع نائب رئيس مجلس الأمناء<br><strong>السيد فريدي يوسف</strong>')" />
    </div>

    <div class="avoid-break mt-1">
        <x-contract.dual rowClass="mt-1 fw-bold" :en="'And'" :ar="$Glyphs('و')" />
        <x-contract.dual rowClass="mt-1" :en="$L('Second Party:')" :ar="$L($Glyphs('الفريق الثاني:'))" />
        
        <x-contract.dual 
            :en="'Ms./Mrs.: <strong>' . $esc($nameEn) . '</strong>'" 
            :ar="$Glyphs('الاسم الثلاثي:').' <strong>'.$escAr($nameAr).'</strong>'" />

        <x-contract.dual 
            :en="'Date and Place of Birth: <strong>' . $esc($pobEn) . ' — ' . $esc($dobStr) . '</strong>'"
            :ar="$Glyphs('مكان وتاريخ الولادة:').' <strong>'.$escAr($pobAr).' — '.$esc($dobStrAr).'</strong>'" />

        <x-contract.dual 
            rowClass="mt-3" 
            :en="'National ID number: <strong>' . $esc($nationalId) . '</strong>'"
            :ar="$Glyphs('الرقم الوطني:').' <strong>'.$esc($Digits($nationalId ?? '')).'</strong>'" />

        <x-contract.dual 
            :en="'Nationality: <strong>' . $esc($natEn) . '</strong>'" 
            :ar="$Glyphs('الجنسية:').' <strong>'.$escAr($natAr).'</strong>'" />

        <x-contract.dual 
            :en="'Address: <strong>' . $esc($addressEn) . '</strong>'" 
            :ar="$Glyphs('العنوان:').' <strong>'.$escAr($addressAr).'</strong>'" />
    </div>

    <div class="avoid-break mt-4">
        <x-contract.dual rowClass="fw-bold" :en="'Preamble:'" :ar="$Glyphs('مقدمة العقد:')" />
        <x-contract.dual 
            style="margin-top:-3px" 
            :en="'Since the first party oversees the implementation of programs in Syria and the nature of the program requires a volunteer in the capacity of <strong>' . $esc($positionEN) . '</strong>'"
            :ar="$Glyphs('لما كان الفريق الأول يشرف على تنفيذ البرامج في سوريا، وطبيعة البرنامج تحتاج إلى متطوع بصفة ').'<strong>'.$escAr($positionAR).'</strong>'" />

        <x-contract.dual 
            :en="'to carry out the tasks mentioned in the attached job description,'" 
            :ar="$Glyphs('لتنفيذ المهام المذكورة في التوصيف الوظيفي المرفق،')" />

        <x-contract.dual 
            :en="'And since the second team has shown willingness to undertake this voluntary work starting from the date of signing this contract,'" 
            :ar="$Glyphs('ولما كان الفريق الثاني قد أبدى استعداده للقيام بهذا العمل التطوعي ابتداءً من تاريخ توقيع هذا العقد،')" />

        <x-contract.dual 
            :en="'The contract has been made between the two parties with mutual agreement of the following:'" 
            :ar="$Glyphs('تم العقد بين الطرفين والقبول المشترك لما يلي:')" />
    </div>

    <div class="avoid-break mt-3">
        <x-contract.dual 
            :en="$LB('1. Contract Preamble:') . ' an integral part of it.'" 
            :ar="$LB($Glyphs('1. مقدمة العقد:')) . ' ' . $Glyphs('جزء لا يتجزأ منه.')" />

        <x-contract.dual 
            :en="$LB('2. Contract type:') . ' <strong>Voluntary Service Contract.</strong>'"
            :ar="$LB($Glyphs('2. نوع العقد:')). ' <strong>'.$Glyphs('عقد تقديم خدمة تطوعيّّة محددة.').'</strong>'" />

        <x-contract.dual 
            :en="$LB('3. Place of Work:') . ' <strong>' . $esc($workPlaceEn) . '</strong>'"
            :ar="$LB($Glyphs('3. مكان العمل:')). ' <strong>'.$escAr($workPlaceAr).'</strong>'" />
    </div>

</div> --}}





















{{-- 
@php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn($s) => $forPdf ? AR::glyphs((string) $s) : (string) $s;
    $Digits = fn($s) => $forPdf ? ar_digits((string) $s) : (string) $s;

    $emp = $contract->employee;
    $position = $contract->position;
    $program = $position?->program;

    // Names
    $nameEn = trim(($emp?->first_name_en ?? '') . ' ' . ($emp?->father_name_en ?? '') . ' ' . ($emp?->last_name_en ?? ''));
    $nameEn = $nameEn !== '' ? $nameEn : '—';
    $nameAr = trim(($emp?->first_name_ar ?? '') . ' ' . ($emp?->father_name_ar ?? '') . ' ' . ($emp?->last_name_ar ?? ''));
    $nameAr = $nameAr !== '' ? $nameAr : '—';

    // Dates & ID
    $dobStr = $emp?->dob ? Carbon::parse($emp->dob)->format('d/m/Y') : '—';
    $nationalId = $emp?->national_number ?? '—';

    // Location
    $centerNameEn = $position?->center?->name_en ?? '—';
    $centerNameAr = $position?->center?->name_ar ?? '—';
    $cityNameEn = $position?->city?->name_en ?? '—';
    $cityNameAr = $position?->city?->name_ar ?? '—';
    $workPlaceEn = $cityNameEn . ' - ' . $centerNameEn;
    $workPlaceAr = $cityNameAr . ' - ' . $centerNameAr;

    $esc = fn($v) => e((string) ($v ?: '—'));
    $escAr = fn($v) => e((string) ($v ? $Glyphs($v) : '—'));

    $L = fn(string $t) => '<span class="h-label">' . $t . '</span>';
    $LB = fn(string $t) => '<span class="h-label-bold">' . $t . '</span>';
@endphp

<div class="contract-title-center">
    <table class="title-table">
        <tr>
            <td class="fw-title">Voluntary Service Contract</td>
            <td class="contract-title-separator">|</td>
            <td class="fw-title" style="direction: rtl;">{{ $Glyphs('عقد تقديم خدمة تطوعيّّة محددة') }}</td>
        </tr>
    </table>
</div>

<div class="avoid-break mt-4">
    <x-contract.dual rowClass="fw-bold" :en="'Between'" :ar="$Glyphs('بين')" />
    <x-contract.dual rowClass="mt-1" :en="$L('First Party:')" :ar="$L($Glyphs('الفريق الأول:'))" />
    <x-contract.dual 
        :en="'<strong>Christian Hope Center - Syria</strong><br>Represented by Mr. Fredy Youssef'"
        :ar="$Glyphs('<strong>مؤسسة مركز الرجاء المسيحي - سوريا</strong><br>ينوب عنها السيد فريدي يوسف')" />
</div>

<div class="avoid-break mt-2">
    <x-contract.dual rowClass="fw-bold" :en="'And'" :ar="$Glyphs('و')" />
    <x-contract.dual :en="$L('Second Party:')" :ar="$L($Glyphs('الفريق الثاني:'))" />
    
    <x-contract.dual :en="'Name: <strong>' . $esc($nameEn) . '</strong>'" :ar="$Glyphs('الاسم: ') . '<strong>' . $escAr($nameAr) . '</strong>'" />
    <x-contract.dual :en="'National ID: <strong>' . $esc($nationalId) . '</strong>'" :ar="$Glyphs('الرقم الوطني: ') . '<strong>' . $Digits($nationalId) . '</strong>'" />
    <x-contract.dual :en="'Address: <strong>' . $esc($emp->address_en) . '</strong>'" :ar="$Glyphs('العنوان: ') . '<strong>' . $escAr($emp->address_ar) . '</strong>'" />
</div>

<div class="avoid-break mt-4">
    <x-contract.dual rowClass="fw-bold" :en="'Preamble:'" :ar="$Glyphs('مقدمة العقد:')" />
    <x-contract.dual 
        :en="'The first party requires a volunteer in the capacity of <strong>' . $esc($position->title_en) . '</strong>.'"
        :ar="$Glyphs('يحتاج الفريق الأول إلى متطوع بصفة ') . '<strong>' . $escAr($position->title_ar) . '</strong>.'" />
</div>

<div class="avoid-break mt-3">
    <x-contract.dual :en="$LB('1. Contract Preamble:') . ' an integral part of it.'" :ar="$LB($Glyphs('1. مقدمة العقد:')) . ' ' . $Glyphs('جزء لا يتجزأ منه.')" />
    <x-contract.dual :en="$LB('2. Contract type:') . ' <strong>Voluntary Service.</strong>'" :ar="$LB($Glyphs('2. نوع العقد: ')) . '<strong>' . $Glyphs('عقد تقديم خدمة تطوعية') . '</strong>'" />
    <x-contract.dual :en="$LB('3. Place of Work:') . ' <strong>' . $esc($workPlaceEn) . '</strong>'" :ar="$LB($Glyphs('3. مكان العمل: ')) . '<strong>' . $escAr($workPlaceAr) . '</strong>'" />
</div> --}}




@php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn($s) => $forPdf ? AR::glyphs((string) $s) : (string) $s;
    $SmartGlyphs = fn($s) => $forPdf ? AR::smartGlyphs((string) $s) : (string) $s;
    $Digits = fn($s) => $forPdf ? ar_digits((string) $s) : (string) $s;

    $loc      = $locale ?? app()->getLocale();
    $emp      = $contract->employee;
    $position = $contract->position;
    $program  = $position?->program;

    $nameEn = trim(($emp?->first_name_en ?? '') . ' ' . ($emp?->father_name_en ?? '') . ' ' . ($emp?->last_name_en ?? ''));
    $nameEn = $nameEn !== '' ? $nameEn : '—';

    $nameAr = trim(($emp?->first_name_ar ?? '') . ' ' . ($emp?->father_name_ar ?? '') . ' ' . ($emp?->last_name_ar ?? ''));
    $nameAr = $nameAr !== '' ? $nameAr : '—';

    $pobEn = trim((string) ($emp?->pob_en ?? '—'));
    $pobAr = trim((string) ($emp?->pob_ar ?? '—'));

    $dob      = $emp?->dob;
    $dobStr   = $dob ? Carbon::parse($dob)->format('d/m/Y') : '—';
    $dobStrAr = $Digits($dobStr);

    $nationalId = $emp?->national_number;
    $addressEn  = $emp?->address_en ?? '—';
    $addressAr  = $emp?->address_ar ?? '—';

    $nationalityRaw = (string) ($emp?->nationality ?? '');
    $natKey         = strtoupper(trim($nationalityRaw));
    $NATIONALITY_MAP = [
        '1'   => ['en' => 'Syrian', 'ar' => 'سوري'],
        'SY'  => ['en' => 'Syrian', 'ar' => 'سوري'],
        'SYR' => ['en' => 'Syrian', 'ar' => 'سوري'],
    ];
    $natEn = $NATIONALITY_MAP[$natKey]['en'] ?? ($nationalityRaw ?: '—');
    $natAr = $Glyphs($NATIONALITY_MAP[$natKey]['ar'] ?? ($nationalityRaw ?: '—'));

    $req        = $position?->requiredPosition;
    $positionEN = $req?->title_en ?? ($position?->title_en ?? '—');
    $positionAR = $req?->title_ar ?? ($position?->title_ar ?? '—');

    $centerNameEn = $position?->center?->name_en ?? $position?->center?->name ?? '—';
    $centerNameAr = $position?->center?->name_ar ?? $position?->center?->name ?? '—';
    $cityNameEn   = $position?->city?->name_en ?? '—';
    $cityNameAr   = $position?->city?->name_ar ?? '—';

    $workPlaceEn  = $cityNameEn . ' - ' . $centerNameEn;
    $workPlaceAr  = $cityNameAr . ' - ' . $centerNameAr;

    $esc   = fn($v) => e((string) ($v === '' || $v === null ? '—' : $v));
    $escAr = fn($v) => e((string) ($v === '' || $v === null ? '—' : $Glyphs($v)));

    $L  = fn(string $t) => '<span class="h-label">' . $t . '</span>';
    $LB = fn(string $t) => '<span class="h-label-bold">' . $t . '</span>';

$preambleSentenceAr = $SmartGlyphs(
        'لما كان الفريق الأول يشرف على تنفيذ البرامج في سوريا،<br>وطبيعة البرنامج تحتاج إلى متطوع بصفة<br><strong>' . $esc($positionAR) . '</strong>،'
    );

    $preambleWillingnessAr = $SmartGlyphs(
        'ولما كان الفريق الثاني قد أبدى استعداده للقيام بهذا العمل<br>التطوعي ابتداءً من تاريخ توقيع هذا العقد،'
    );

    $preambleTasksAr      = $SmartGlyphs('لتنفيذ المهام المذكورة في التوصيف الوظيفي المرفق،');
    $preambleConclusionAr = $SmartGlyphs('تم العقد بين الطرفين والقبول المشترك لما يلي:');
@endphp

<div class="avoid-break contract-title-center">
    <div class="dual-row dual-tight">
        <div class="dual-en">Voluntary Service Contract</div>
        <div class="contract-title-separator">|</div>
        <div class="dual-ar">{{ $Glyphs('عقد تقديم خدمة تطوعيّّة محددة') }}</div>
    </div>
</div>

<div class="avoid-break mt-2">
    <x-contract.dual rowClass="fw-bold" :en="'Between'" :ar="$Glyphs('بين')" />
    <x-contract.dual rowClass="mt-1" :en="$L('First Party:')" :ar="$L($Glyphs('الفريق الأول:'))" />
    <x-contract.dual 
    :en="'<strong>Christian Hope Center - Syria</strong><br>Represented by the Vice chairman of the board of trustees <strong>Mr. Fredy Youssef</strong>'"
    :ar="'<strong>' . $Glyphs('مؤسسة مركز الرجاء المسيحي - سوريا') . '</strong><br>' . 
         $Glyphs('ينوب عنها بالتوقيع نائب رئيس مجلس الأمناء') . '<br>' . 
         '<strong>' . $Glyphs('السيد فريدي يوسف') . '</strong>'" 
/>
</div>

<div class="avoid-break mt-2">
    <x-contract.dual rowClass="fw-bold" :en="'And'"           :ar="$Glyphs('و')" />
    <x-contract.dual rowClass="mt-1"    :en="$L('Second Party:')" :ar="$L($Glyphs('الفريق الثاني:'))" />

    <x-contract.dual
        :en="'Ms./Mrs.: <strong>' . $esc($nameEn) . '</strong>'"
        :ar="'<strong>' . $escAr($nameAr) . '</strong> ' . $Glyphs('الاسم الثلاثي:')" />
        

    <x-contract.dual
        :en="'Date and Place of Birth: <strong>' . $esc($pobEn) . ' — ' . $esc($dobStr) . '</strong>'"
        :ar="'<strong>' . $esc($dobStr) . ' — ' . $escAr($pobAr) . '</strong> ' . $Glyphs('مكان وتاريخ الولادة:')" />

    <x-contract.dual rowClass="mt-1"
        :en="'National ID number: <strong>' . $esc($nationalId) . '</strong>'"
        :ar="'<strong>' . $esc($nationalId) . '</strong> ' . $Glyphs('الرقم الوطني:')" />

    <x-contract.dual
        :en="'Nationality: <strong>' . $esc($natEn) . '</strong>'"
        :ar="'<strong>' . $escAr($natAr) . '</strong> ' . $Glyphs('الجنسية:')" />

    <x-contract.dual
        :en="'Address: <strong>' . $esc($addressEn) . '</strong>'"
        :ar="'<strong>' . $escAr($addressAr) . '</strong> ' . $Glyphs('العنوان:')" />
</div>

<div class="avoid-break mt-2">
    <x-contract.dual
        :en="'<strong>Preamble:</strong>'"
        :ar="'<strong>' . $Glyphs('مقدمة العقد:') . '</strong>'" />

    <x-contract.dual style="margin-top: 1px;"
        :en="'Since the first party oversees the implementation of programs in Syria and the nature of the program requires a volunteer in the capacity of <strong>' . $esc($positionEN) . '</strong>,'"
        :ar="$preambleSentenceAr" />

    <x-contract.dual style="margin-top: 1px;"
        :en="'to carry out the tasks mentioned in the attached job description,'"
        :ar="$preambleTasksAr" />

    <x-contract.dual style="margin-top: 1px;"
        :en="'And since the second team has shown willingness to undertake this voluntary work starting from the date of signing this contract,'"
        :ar="$preambleWillingnessAr" />

    <x-contract.dual style="margin-top: 1px;"
        :en="'The contract has been made between the two parties with mutual agreement of the following:'"
        :ar="$preambleConclusionAr" />
</div>
<div class="avoid-break mt-2">
    <x-contract.dual
        :en="$LB('1. Contract Preamble:') . ' an integral part of it.'"
        :ar="$Glyphs('جزء لا يتجزأ منه. ') . $LB($Glyphs('مقدمة العقد:') . ' .1')" />

    <x-contract.dual
        :en="$LB('2. Contract type:') . ' <strong>Voluntary Service Contract.</strong>'"
        :ar="'<strong>' . $Glyphs('عقد تقديم خدمة تطوعيّة محددة.') . '</strong> ' . $LB($Glyphs('نوع العقد:') . ' .2')" />

    <x-contract.dual
        :en="$LB('3. Place of Work:') . ' <strong>' . $esc($workPlaceEn) . '</strong>'"
        :ar="'<strong>' . $escAr($workPlaceAr) . '</strong> ' . $LB($Glyphs('مكان العمل:') . ' .3')" />
</div>