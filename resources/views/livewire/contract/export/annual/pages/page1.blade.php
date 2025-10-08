@php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string) $s) : (string) $s;

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
    $dobStr = $dob ? Carbon::parse($dob)->format('d/m/Y') : '—';
    $dobStrAr = ar_digits($dobStr);

    // Other info
    $pobEn  = trim((string) $cityEn);
    $pobAr  = trim((string) $cityAr);
    $nationalId = $emp?->national_id;

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

<div class="avoid-break mt-6 " style="line-height: 0.8">
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



<div class="avoid-break mt-6" style="line-height: 0.8">
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



<div class="avoid-break mt-6" style="line-height: 0.7">
    <x-contract.dual rowClass="mt-3 fw-bold" :en="'And'" :ar="$Glyphs('و')"/>
    <x-contract.dual rowClass="mt-1" :en="$L('Second Party:')" :ar="$L($Glyphs('الفريق الثاني:'))"/>

    <div style="line-height: 0.7">
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

<div class="avoid-break mt-6">
    <x-contract.dual :en="$L('Preamble:')" :ar="$L($Glyphs('المقدمة:'))"/>
    <x-contract.dual style="margin-top: -3px"
        :en="'Whereas the Volunteer has stated that he/she possesses the required qualifications for <strong>'.$esc($positionEN).'</strong> (the qualifications are attached to the job description),<br>  and on the basis thereof has offered to volunteer for “'.$esc($program).'” as a <strong>'.$esc($positionEN).'</strong>, starting  from the  signing <br> date of this contract.'"
        :ar="$Glyphs(__('general.text10', ['position' => $positionAR, 'program' => $program]))"
    />
</div>
