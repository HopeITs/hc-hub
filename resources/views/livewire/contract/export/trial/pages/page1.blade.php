@php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;
    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string) $s) : (string) $s;

    $loc = $locale ?? app()->getLocale();
    $emp = $contract->employee;

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
            : ($emp->address ?? ''));

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
            : ($emp->address ?? '')));

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

    // Escaping
    $esc   = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));
    $escAr = fn ($v) => e((string)($v === '' || $v === null ? '—' : $Glyphs($v)));

    // Label generator
    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';
@endphp

<div class="avoid-break">
    <x-contract.dual tight
                     :en="$L('PRIVATE AND CONFIDENTIAL').' — Volunteering contract for a limited period'"
                     :ar="$Glyphs('عقد تطوعي لمدة محدودة').' — '.$L($Glyphs('سري وخاص'))"
                     enClass="text-base" arClass="text-base"
    />
</div>

<x-contract.dual rowClass="mt-3 fw-bold" :en="'Between'" :ar="$Glyphs('بين')"/>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-1" :en="$L('First Party:')" :ar="$L($Glyphs('الفريق الأول:'))"/>
    <x-contract.dual
        :en="'Christian Hope Center - Aleppo / Head office <br><strong> represented in this Contract by Mr. Fredy Youssef</strong>'"
        :ar="$Glyphs('مركز الرجاء المسيحي - حلب / الإدارة العامة الممثل في هذا العقد بالسيد فريدي يوسف')"
    />
    <x-contract.dual
        :en="'Mr. Fredy Youssef is delegated by the Latin church in Syria to manage The Christian Hope Center project and all affiliated programs.'"
        :ar="$Glyphs('إن السيد فريدي يوسف تم تفويضه من قبل النيابة الرسولية اللاتينية في سوريا لإدارة مشروع مركز الرجاء وكل البرامج التابعة له.')"
    />
</div>

<x-contract.dual rowClass="mt-3 fw-bold" :en="'And'" :ar="$Glyphs('و')"/>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-1" :en="$L('Second Party:')" :ar="$L($Glyphs('الفريق الثاني:'))"/>
    <x-contract.dual :en="'<strong>Ms./Mrs.</strong> '.$esc($nameEn)"
                     :ar="$escAr($nameAr).' '.$Glyphs('السيد/ة')"/>
    <x-contract.dual :en="'<strong>Date and Place of Birth:</strong> '.$esc($pobEn).' — '.$esc($dobStr)"
                     :ar="$escAr($dobStrAr).' — '.$escAr($pobAr).' '.$Glyphs('مكان وتاريخ الولادة')"/>
    <x-contract.dual :en="'<strong>National ID number:</strong> '.$esc($nationalId)"
                     :ar="$escAr(ar_digits($nationalId ?? '')).' '.$Glyphs('الرقم الوطني')"/>
    <x-contract.dual :en="'<strong>Nationality:</strong> '.$esc($natEn)"
                     :ar="$escAr($natAr).' '.$Glyphs('الجنسية')"/>
    <x-contract.dual :en="'<strong>Address:</strong> '.$esc($addressEn)"
                     :ar="$escAr($addressAr).' '.$Glyphs('العنوان')"/>
    <x-contract.dual :en="'Hereinafter referred to as “<strong>Volunteer</strong>”'"
                     :ar="$Glyphs('المشار إليه في هذا العقد بـ “المتطوع”')"/>
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-4" :en="$L('Preamble:')" :ar="$L($Glyphs('المقدمة:'))"/>
    <x-contract.dual
        :en="'The First Party wants to utilize the expertise of the Second Party as a MEAL Officer, and the Second Party has agreed to this offer.'"
        :ar="$Glyphs('يرغب الفريق الأول في الاستفادة من خبرة الفريق الثاني بصفة مسؤول MEAL وقد وافق الفريق الثاني على هذا العرض.')"
    />
</div>
