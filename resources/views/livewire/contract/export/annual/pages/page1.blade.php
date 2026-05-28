{{-- @php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string) $s) : (string) $s;

    $loc = $locale ?? app()->getLocale();
    $emp = $contract->employee;
    $position = $contract->position;
    $program  = $position?->program;

        $nameEn = $emp?->first_name_en .' ' . $emp?->last_name_en ?? '—';
      $nameAr = $emp?->first_name_ar .' ' . $emp?->last_name_ar ?? '—';

      $cityEn = $emp?->city?->name_en
          ?? $emp?->city?->name
          ?? $emp?->place_of_birth ?? '—';

      $cityAr = $emp?->city?->name_ar
          ?? $emp?->city?->name
          ?? $emp?->place_of_birth ?? '—';

      $addressEn = $emp?->address_en ?? '—';
      $addressAr = $emp?->address_ar ?? '—';

    // DOB
    $dob = $emp?->dob;
    $dobStr = $dob ? Carbon::parse($dob)->format('d/m/Y') : '—';
    $dobStrAr = ar_digits($dobStr);

    // Other info
    $pobEn  = trim((string) $cityEn);
    $pobAr  = trim((string) $cityAr);
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

    $positionAR= $position?->title_ar ?? '—';
    $positionEN= $position?->title ?? '—';


    $program  = $program?->name ?? '—';


    // Escaping
    $esc   = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));
    $escAr = fn ($v) => e((string)($v === '' || $v === null ? '—' : $Glyphs($v)));

    // Label generator
    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';
@endphp

<div class="mt-6 avoid-break " style="line-height: 0.8">
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



<div class="mt-6 avoid-break" style="line-height: 0.8">
    <x-contract.dual rowClass=" fw-bold" :en="'Between'" :ar="$Glyphs('بين')"/>
    <x-contract.dual rowClass="mt-1" :en="$L('First Party:')" :ar="$L($Glyphs('الفريق الأول:'))"/>
    <div style="margin-top: -4px">
        <x-contract.dual
            :en="'Christian Hope Center - Aleppo / Head office <br><strong> represented in this Contract by Mr. Fredy Youssef</strong> <br><br>'"
            :ar="$Glyphs(__('general.text1'))"
        />
    </div>
    <div style="line-height: 0.8 " >
        <x-contract.dual
            :en="'Mr. Fredy Youssef is delegated by the <br>Latin church  in Syria to manage The Christian<br> Hope Center  and all affiliated programs.'"
            :ar="$Glyphs(__('general.text2'))"
        />
    </div>
    <div class="mt-6">
        <x-contract.dual
            :en="'Hereinafter referred to as “<strong>Christian Hope <br>  Center - Aleppo - Head Office</strong>”'"
            :ar="$Glyphs(__('general.text3'))"
        />
    </div>
</div>



<div class="mt-6 avoid-break" style="line-height: 0.7">
    <x-contract.dual rowClass="mt-3 fw-bold" :en="'And'" :ar="$Glyphs('و')"/>
    <x-contract.dual rowClass="mt-1" :en="$L('Second Party:')" :ar="$L($Glyphs('الفريق الثاني:'))"/>

    <div style="line-height: 0.7">
        <x-contract.dual
            :en="'<strong>Ms./Mrs.</strong> '.$esc($nameEn)"
            :ar="$escAr($nameAr).' '.$Glyphs(__('general.text4'))"/>
        <x-contract.dual
            :en="'<strong>Date and Place of Birth:</strong> '.$esc($pobEn).' — '.$esc($dobStr)"
            :ar="($dobStrAr).' — '.$escAr($pobAr).$Glyphs(__('general.text5'))"/>

        <x-contract.dual
            :en="'<strong>National ID number:</strong> '.$esc($nationalId)"
            :ar="$escAr(ar_digits($nationalId ?? '')).' '.$Glyphs(__('general.text6'))"/>
        <x-contract.dual
            :en="'<strong>Nationality:</strong> '.$esc($natEn)"
            :ar="($natAr).' '.$Glyphs(__('general.text7'))"/>
        <x-contract.dual
            :en="'<strong>Address:</strong> '"
            :ar="$Glyphs(__('general.text8'))"/>
        <x-contract.dual
            :en="$esc($addressEn)"
            :ar="$esc($addressAr)"/>
    </div>
</div>

<div class="mt-6 avoid-break">
    <x-contract.dual :en="$L('Preamble:')" :ar="$L($Glyphs('المقدمة:'))"/>
    <x-contract.dual style="margin-top: -3px"
        :en="'Whereas the Volunteer has stated that he/she possesses the required qualifications for <strong>'.$esc($positionEN).'</strong> (the qualifications are attached to the job description),<br>  and on the basis thereof has offered to volunteer for “'.$esc($program).'” as a <strong>'.$esc($positionEN).'</strong>, starting  from the  signing <br> date of this contract.'"
        :ar="$Glyphs(__('general.text10', ['position' => $positionAR, 'program' => $program]))"
    />
</div> --}}
@php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    // PDF Helpers for Right-to-Left and Digit conversion
    $Glyphs = fn($s) => $forPdf ? AR::glyphs((string) $s) : (string) $s;
    $SmartGlyphs = fn($s) => $forPdf ? AR::smartGlyphs((string) $s) : (string) $s;
    $Digits = fn($s) => $forPdf ? AR::digits((string) $s) : (string) $s;

    $loc      = $locale ?? app()->getLocale();
    $emp      = $contract->employee;
    $position = $contract->position;
    $program  = $position?->program;

    // ── Name Construction ──
    $nameEn = trim(($emp?->first_name_en ?? '') . ' ' . ($emp?->father_name_en ?? '') . ' ' . ($emp?->last_name_en ?? ''));
    $nameEn = $nameEn !== '' ? $nameEn : '—';

    $nameAr = trim(($emp?->first_name_ar ?? '') . ' ' . ($emp?->father_name_ar ?? '') . ' ' . ($emp?->last_name_ar ?? ''));
    $nameAr = $nameAr !== '' ? $nameAr : '—';

    // ── Place & Date of Birth ──
    $cityEn = $emp?->city?->name_en ?? ($emp?->city?->name ?? ($emp?->place_of_birth ?? '—'));
    $cityAr = $emp?->city?->name_ar ?? ($emp?->city?->name ?? ($emp?->place_of_birth ?? '—'));
    $pobEn  = trim((string) $cityEn);
    $pobAr  = trim((string) $cityAr);

    $dob      = $emp?->dob;
    $dobStr   = $dob ? Carbon::parse($dob)->format('d/m/Y') : '—';
    $dobStrAr = $Digits($dobStr);

    // ── Identity & Nationality ──
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

    // ── Position & Work Place ──
    $req        = $position?->requiredPosition;
    $positionEN = $req?->title_en ?? ($position?->title_en ?? '—');
    $positionAR = $req?->title_ar ?? ($position?->title_ar ?? '—');
    $program    = $program?->name ?? '—';

    $centerNameEn = $position?->center?->name_en ?? $position?->center?->name ?? '—';
    $centerNameAr = $position?->center?->name_ar ?? $position?->center?->name ?? '—';
    $cityNameEn   = $position?->city?->name_en ?? '—';
    $cityNameAr   = $position?->city?->name_ar ?? '—';

    $workPlaceEn  = $cityNameEn . ' - ' . $centerNameEn;
    $workPlaceAr  = $cityNameAr . ' - ' . $centerNameAr;

    // ── Text Helpers ──
    $esc   = fn($v) => e((string) ($v === '' || $v === null ? '—' : $v));
    $escAr = fn($v) => e((string) ($v === '' || $v === null ? '—' : $Glyphs($v)));

    $L  = fn(string $t) => '<span class="h-label">' . $t . '</span>';
    $LB = fn(string $t) => '<span class="h-label-bold">' . $t . '</span>';

    // ── Pre-processed Arabic Smart Blocks ──
    $preambleSentenceAr = $SmartGlyphs('لما كان الفريق الأول يشرف على تنفيذ البرامج في سوريا،<br>وطبيعة البرنامج تحتاج إلى متطوع بصفة<br><strong>' . $esc($positionAR) . '</strong>،');
    $preambleWillingnessAr = $SmartGlyphs('ولما كان الفريق الثاني قد أبدى استعداده للقيام بهذا العمل<br>التطوعي ابتداءً من تاريخ توقيع هذا العقد،');
    $preambleTasksAr      = $SmartGlyphs('لتنفيذ المهام المذكورة في التوصيف الوظيفي المرفق،');
    $preambleConclusionAr = $SmartGlyphs('تم العقد بين الطرفين والقبول المشترك لما يلي:');
@endphp

<div>
    
    {{-- ── TITLE SECTION ── --}}
    <div class="avoid-break contract-title-center">
        <div class="dual-row dual-tight">
            <div class="dual-en">Voluntary Contract</div>
            <div class="contract-title-separator">|</div>
            <div class="dual-ar">{{ $Glyphs('عقد تطوّع') }}</div>
        </div>
    </div>

    {{-- ── FIRST PARTY ── --}}
    <div class="avoid-break mt-1">
        <x-contract.dual rowClass="fw-bold" :en="'Between'" :ar="$Glyphs('بين')" />
        <x-contract.dual rowClass="mt-1" :en="$L('First Party:')" :ar="$L($Glyphs('الفريق الأول:'))" />
        <x-contract.dual 
            :en="'<strong>Christian Hope Center - Syria</strong><br>Represented by the Vice chairman of the board of trustees<br><strong>Mr. Fredy Youssef</strong>'"
            :ar="'<strong>' . $Glyphs('مؤسسة مركز الرجاء المسيحي - سوريا') . '</strong><br>' . $Glyphs('ينوب عنها بالتوقيع نائب رئيس مجلس الأمناء') . '<br><strong>' . $Glyphs('السيد فريدي يوسف') . '</strong>'" 
        />
    </div>

    {{-- ── SECOND PARTY ── --}}
    <div class="avoid-break mt-1">
        <x-contract.dual rowClass="fw-bold" :en="'And'" :ar="$Glyphs('و')" />
        <x-contract.dual rowClass="mt-1" :en="$L('Second Party:')" :ar="$L($Glyphs('الفريق الثاني:'))" />
        
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

    {{-- ── PREAMBLE SECTION ── --}}
    <div class="avoid-break mt-1">
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

    {{-- ── ARTICLES 1-3 ── --}}
    <div class="avoid-break mt-1">
        <x-contract.dual
            :en="$LB('1. Contract Preamble:') . ' an integral part of it.'"
            :ar="$Glyphs('جزء لا يتجزأ منه. ') . $LB($Glyphs('مقدمة العقد:') . ' .1')" />

        <x-contract.dual
            :en="$LB('2. Contract type:') . ' <strong>Volunteer agreement for a limited period.</strong>'"
            :ar="'<strong>' . $Glyphs('عقد تطوعي لمدة محدودة.') . '</strong> ' . $LB($Glyphs('نوع العقد:') . ' .2')" />

        <x-contract.dual
            :en="$LB('3. Place of Work:') . ' <strong>' . $esc($workPlaceEn) . '</strong>'"
            :ar="'<strong>' . $escAr($workPlaceAr) . '</strong> ' . $LB($Glyphs('مكان العمل:') . ' .3')" />
    </div>

</div>