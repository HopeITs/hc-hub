@php
    use Carbon\Carbon;
    use App\Support\ArabicText as AR;

    $forPdf = $forPdf ?? false;

    $Glyphs = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $Digits = fn ($s) => $forPdf ? ar_digits((string)$s) : (string)$s;

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
            : ($emp->address ?? '')));

    // DOB
    $dob = $emp?->dob;
    $dobStr   = $dob ? Carbon::parse($dob)->format('d/m/Y') : '—';
    $dobStrAr = $Digits($dobStr);

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

    // Role from position (service contract specific)
    $t = function ($model, string $field, string $lang, $fallback = null) {
        if (! $model) return $fallback;
        if (method_exists($model, 'getTranslation')) {
            $v = $model->getTranslation($field, $lang);
            return ($v === null || $v === '') ? $fallback : $v;
        }
        $raw = $model->{$field} ?? null;
        if (is_array($raw)) {
            $v = $raw[$lang] ?? reset($raw);
            return ($v === null || $v === '') ? $fallback : $v;
        }
        return ($raw === null || $raw === '') ? $fallback : $raw;
    };

    $position = $contract->position;
    $positionEN = $position?->title ?? '—';
    $positionAR = $position?->title_ar ?? '—';

    // Escaping
    $esc   = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));
    $escAr = fn ($v) => e((string)($v === '' || $v === null ? '—' : $Glyphs($v)));

    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';
@endphp

<div class="text-center mb-8 text-primary">
    <h2 class="text-[18px] font-bold">عقد اتفاق تطوعي</h2>
</div>

<div class="avoid-break">
    <x-contract.dual tight :en="$L('PRIVATE AND CONFIDENTIAL')" :ar="$L($Glyphs('سري وخاص'))" enClass="text-base" arClass="text-base"/>
    <x-contract.dual tight :en="'Volunteer agreement for a limited period'" :ar="$Glyphs('عقد تطوعي لمدة محدودة')" enClass="text-base" arClass="text-base"/>
</div>

<div class="avoid-break mt-8">
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

<div class="avoid-break mt-8">
    <x-contract.dual rowClass="mt-3 fw-bold" :en="'And'" :ar="$Glyphs('و')"/>
    <x-contract.dual rowClass="mt-1" :en="$L('Second Party:')" :ar="$L($Glyphs('الفريق الثاني:'))"/>
    <x-contract.dual :en="'<strong>Ms./Mrs.</strong> '.$esc($nameEn)" :ar="'<strong>'.$Glyphs('السيد/ة:').'</strong> '.$esc($nameAr)"/>
    <x-contract.dual :en="'<strong>Date and Place of Birth:</strong> '.$esc($pobEn).' — '.$esc($dobStr)" :ar="'<strong>'.$Glyphs('مكان وتاريخ الولادة:').'</strong> '.$esc($pobAr).' — '.$esc($dobStrAr)"/>
    <x-contract.dual :en="'<strong>National ID number:</strong> '.$esc($nationalId)" :ar="'<strong>'.$Glyphs('الرقم الوطني:').'</strong> '.$esc($Digits($nationalId ?? ''))"/>
    <x-contract.dual :en="'<strong>Nationality:</strong> '.$esc($natEn)" :ar="'<strong>'.$Glyphs('الجنسية:').'</strong> '.$esc($natAr)"/>
    <x-contract.dual :en="'<strong>Address:</strong> '.$esc($addressEn)" :ar="'<strong>'.$Glyphs('العنوان:').'</strong> '.$esc($addressAr)"/>
</div>

<div class="avoid-break mt-8">
    <x-contract.dual :en="$L('Contract Introduction:')" :ar="$L($Glyphs('مقدمة العقد:'))"/>
    <x-contract.dual style="margin-top:-3px"
                     :en="'The First Party wants to utilize the expertise of the second party as a <strong>'.$esc($positionEN).'</strong> and offered the second party the position whereas the second  party has agreed to this offer.'"
                     :ar="$Glyphs('حيث أن الفريق الأول يرغب في الاستعانة بخبرة الفريق الثاني لكونه <strong>'.$esc($positionAR).'</strong> وعرض وعلى الفريق الثاني القيام بذلك وحيث أن هذا العرض قد لاقى قبول الفريق الثاني.')"/>
</div>
