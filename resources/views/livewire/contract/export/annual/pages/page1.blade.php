@php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string) $s) : (string) $s;

    $loc = $locale ?? app()->getLocale();
    $emp = $contract->employee;
    $position = $contract->position;
    $program  = $position?->program;

    // English fields
    $nameEn = $emp?->getTranslation('name', 'en')
        ?? $emp?->getTranslation('name', $loc)
        ?? $emp?->name ?? '—';

    $cityEn = $emp?->city?->getTranslation('name', 'en')
        ?? $emp?->city?->name
        ?? $emp?->place_of_birth ?? '';

    $addressEn = $emp?->getTranslation('address', 'en')
        ?? $emp?->getTranslation('address', $loc)
        ?? (is_array($emp?->address ?? null)
            ? ($emp->address['en'] ?? reset($emp->address) ?? '')
            : ($emp->address ?? '') );

    // Arabic fields
    $nameAr = $Glyphs($emp?->getTranslation('name', 'ar')
        ?? $emp?->getTranslation('name', $loc)
        ?? $emp?->name ?? '—');

    $cityAr = $Glyphs($emp?->city?->getTranslation('name', 'ar')
        ?? $emp?->city?->name
        ?? $emp?->place_of_birth ?? '');

    $addressAr = $Glyphs($emp?->getTranslation('address', 'ar')
        ?? $emp?->getTranslation('address', $loc)
        ?? (is_array($emp?->address ?? null)
            ? ($emp->address['ar'] ?? reset($emp->address) ?? '')
            : ($emp->address ?? '') ));

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

    $position= $position?->title ?? '—';

    $program  = $program?->name ?? '—';


    // Escaping
    $esc   = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));
    $escAr = fn ($v) => e((string)($v === '' || $v === null ? '—' : $Glyphs($v)));

    // Label generator
    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';
@endphp

<div class="avoid-break">
    <x-contract.dual tight
                     :en="$L('PRIVATE AND CONFIDENTIAL').' — Volunteer agreement for a limited period'"
                     :ar="$Glyphs('عقد تطوعي لمدة محدودة').' — '.$L($Glyphs('سري وخاص'))"
                     enClass="text-base" arClass="text-base"
    />
</div>

<x-contract.dual rowClass="mt-3 fw-bold" :en="'Between'" :ar="$Glyphs('بين')"/>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-1" :en="$L('First Party:')" :ar="$L($Glyphs('الفريق الأول:'))"/>
    <x-contract.dual
        :en="'Christian Hope Center - Aleppo / Head office <br><strong> represented in this Contract by Mr. Fredy Youssef</strong> <br><br>'"
        :ar="$Glyphs(__('general.text1'))"
    />
    <x-contract.dual
        :en="'Mr. Fredy Youssef is delegated by the <br>Latin church  in Syria to manage The Christian<br> Hope Center  and all affiliated programs.'"
        :ar="$Glyphs(__('general.text2'))"
    />
    <x-contract.dual
        :en="'Hereinafter referred to as “<strong>Christian Hope <br>  Center - Aleppo - Head Office</strong>”'"
        :ar="$Glyphs(__('general.text3'))"
    />
</div>

<x-contract.dual rowClass="mt-3 fw-bold" :en="'And'" :ar="$Glyphs('و')"/>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-1" :en="$L('Second Party:')" :ar="$L($Glyphs('الفريق الثاني:'))"/>

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
    <x-contract.dual
        :en="'Hereinafter referred to as “<strong>Volunteer</strong>”'"
        :ar="$Glyphs(__('general.text10', ['position' => $position, 'program' => $program]))"
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-4" :en="$L('Preamble:')" :ar="$L($Glyphs('المقدمة:'))"/>
    <x-contract.dual
        :en="'Whereas the Volunteer has stated that he/she possesses the required qualifications for <strong>'.$esc($position).'</strong> (the qualifications are attached to the job description), and on the basis thereof has offered to volunteer for “'.$esc($program).'” as a <strong>'.$esc($position).'</strong>, starting from the signing date of this contract.'"
        :ar="$Glyphs(__('general.text10', ['position' => $position, 'program' => $program]))"
    />
</div>
