@php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string) $s) : (string) $s;
    $Digits = fn ($s) => $forPdf ? ar_digits((string)$s) : (string)$s;

    $loc = $locale ?? app()->getLocale();
    $emp = $contract->employee;
    $position = $contract->position;
    $program  = $position?->program;
      $nameEn = $emp?->name_en ?? '—';
      $nameAr = $emp?->name_ar ?? '—';

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
    $dobStr   = $dob ? Carbon::parse($dob)->format('d/m/Y') : '—';
    $dobStrAr = $Digits($dobStr);

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

    // Position and program
    $positionEN = $position?->title_en ?? '—';
    $positionAR = $position?->title_ar ?? '—';
    $program    = $program?->name ?? '—';

    // Escaping
    $esc   = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));
    $escAr = fn ($v) => e((string)($v === '' || $v === null ? '—' : $Glyphs($v)));

    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';
@endphp


<div class="avoid-break ">
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

<div class="avoid-break mt-6">
    <x-contract.dual rowClass="fw-bold" :en="'Between'" :ar="$Glyphs('بين')"/>
    <x-contract.dual rowClass="mt-1" :en="$L('First Party:')" :ar="$L($Glyphs('الفريق الأول:'))"/>
    <x-contract.dual
            :en="'<strong>Christian Hope Center - Aleppo / Head office</strong> represented in this Contract by <strong>Mr. Fredy Youssef</strong>'"
            :ar="$Glyphs('<strong>مركز الرجاء المسيحي - حلب / الإدارة العامة</strong> الممثل في هذا العقد ب<strong>السيد فريدي يوسف</strong>')"/>
    <x-contract.dual
            :en="'<strong>Mr. Fredy Youssef</strong> is delegated by the Latin church in Syria to manage The Christian Hope Center and all affiliated programs.'"
            :ar="$Glyphs('إن السيد فريدي يوسف تم تفويضه من قبل النيابة الرسولية اللاتينية في سوريا لإدارة مركز الرجاء المسيحي وكل البرامج التابعة له.')"/>
    <x-contract.dual
            :en="'Hereinafter referred to as “<strong>Christian Hope Center - Aleppo - Head Office</strong>”'"
            :ar="$Glyphs('المشار إليه في هذا العقد بـ “<strong>مركز الرجاء المسيحي – حلب - الإدارة العامة</strong>”')"/>
</div>

<div class="avoid-break mt-6">
    <x-contract.dual rowClass="mt-3 fw-bold" :en="'And'" :ar="$Glyphs('و')"/>
    <x-contract.dual rowClass="mt-1" :en="$L('Second Party:')" :ar="$L($Glyphs('الفريق الثاني:'))"/>
    <x-contract.dual :en="'<strong>Ms./Mrs.</strong> '.$esc($nameEn)"
                     :ar="'<strong>'.$Glyphs('السيد/ة:').'</strong> '.$esc($nameAr)"/>
    <x-contract.dual :en="'<strong>Date and Place of Birth:</strong> '.$esc($pobEn).' — '.$esc($dobStr)"
                     :ar="'<strong>'.$Glyphs('مكان وتاريخ الولادة:').'</strong> '.$esc($pobAr).' — '.$esc($dobStrAr)"/>
    <x-contract.dual :en="'<strong>National ID number:</strong> '.$esc($nationalId)"
                     :ar="'<strong>'.$Glyphs('الرقم الوطني:').'</strong> '.$esc($Digits($nationalId ?? ''))"/>
    <x-contract.dual :en="'<strong>Nationality:</strong> '.$esc($natEn)"
                     :ar="'<strong>'.$Glyphs('الجنسية:').'</strong> '.$esc($natAr)"/>
    <x-contract.dual :en="'<strong>Address:</strong> '.$esc($addressEn)"
                     :ar="'<strong>'.$Glyphs('العنوان:').'</strong> '.$esc($addressAr)"/>
    <x-contract.dual :en="'Hereinafter referred to as “<strong>Volunteer</strong>”'"
                     :ar="$Glyphs('المشار إليه في هذا العقد بـ “<strong>المتطوع</strong>”')"/>
</div>

<div class="avoid-break mt-6">
    <x-contract.dual :en="$L('Preamble:')" :ar="$L($Glyphs('المقدمة:'))"/>
    <x-contract.dual style="margin-top:-3px"
                     :en="'Whereas the Volunteer has stated that he/she possesses the required qualifications for <strong>'.$esc($positionEN).'</strong> (the qualifications are attached to the job description), and on the basis thereof has offered to volunteer for “'.$esc($program).'” as a <strong>'.$esc($positionEN).'</strong>, starting from the signing date of this contract.'"
                     :ar="$Glyphs(__('general.text10', ['position' => $positionAR, 'program' => $program]))"/>
</div>
