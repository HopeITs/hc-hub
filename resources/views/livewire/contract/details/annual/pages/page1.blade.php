@php
    use App\Support\ArabicText as AR;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;
    $forPdf = $forPdf ?? false;
    $G = fn ($s) => $forPdf ? AR::glyphs((string)$s) : (string)$s;
    $D = fn ($s) => $forPdf ? AR::digits((string)$s) : (string)$s;

    $loc = $locale ?? app()->getLocale();
    $emp = $contract->employee;

    $nameEn = $emp?->getTranslation('name', 'en')
        ?? $emp?->getTranslation('name', $loc)
        ?? $emp?->name ?? '—';

    $cityEn = $emp?->city?->getTranslation('name', 'en')
        ?? $emp?->city?->name
        ?? $emp?->place_of_birth ?? '';

    $addressEn = $emp?->getTranslation('address', 'en')
        ?? $emp?->getTranslation('address', $loc)
        ?? (is_array($emp?->address ?? null) ? ($emp->address['en'] ?? reset($emp->address) ?? '') : ($emp->address ?? '') );

    $nameAr = $G(
        $emp?->getTranslation('name', 'ar')
        ?? $emp?->getTranslation('name', $loc)
        ?? $emp?->name ?? '—'
    );

    $cityAr = $G(
        $emp?->city?->getTranslation('name', 'ar')
        ?? $emp?->city?->name
        ?? $emp?->place_of_birth ?? ''
    );

    $addressAr = $G(
        $emp?->getTranslation('address', 'ar')
        ?? $emp?->getTranslation('address', $loc)
        ?? (is_array($emp?->address ?? null) ? ($emp->address['ar'] ?? reset($emp->address) ?? '') : ($emp->address ?? '') )
    );

    $pobEn  = trim((string) $cityEn);
    $pobAr  = trim((string) $cityAr);

    $dob    = $emp?->dob;
    $dobStr = $dob ? Carbon::parse($dob)->format('d/m/Y') : '—';
    $dobStrAr = $D($dobStr);

    $nationalId = $emp?->national_id;

    $nationalityRaw = (string) ($emp?->nationality ?? '');
    $natKey = strtoupper(trim((string) $nationalityRaw));
    $NATIONALITY_MAP = [
        '1'   => ['en' => 'Syrian', 'ar' => 'سوري'],
        'SY'  => ['en' => 'Syrian', 'ar' => 'سوري'],
        'SYR' => ['en' => 'Syrian', 'ar' => 'سوري'],
    ];
    $natEn = $NATIONALITY_MAP[$natKey]['en'] ?? (string)($nationalityRaw ?: '—');
    $natAr = $G($NATIONALITY_MAP[$natKey]['ar'] ?? (string)($nationalityRaw ?: '—'));

    $esc   = fn ($v) => e((string)($v === '' || $v === null ? '—' : $v));
    $escAr = fn ($v) => e((string)($v === '' || $v === null ? '—' : $G($v)));

    $L = fn (string $t) => '<span class="h-label">'.$t.'</span>';
@endphp

<div class="avoid-break">
    <x-contract.dual tight
                     :en="$L('PRIVATE AND CONFIDENTIAL').' — Volunteer agreement for a limited period'"
                     :ar="$L($G('سري وخاص')).' — '.$G('عقد تطوعي لمدة محدودة')"
                     enClass="text-base" arClass="text-base"
    />
</div>

<x-contract.dual rowClass="mt-3 fw-bold" :en="'Between'" :ar="$G('بين')"/>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-1" :en="$L('First Party:')" :ar="$L($G('الفريق الأول:'))"/>
    <x-contract.dual
        :en="'<strong>Christian Hope Center - Aleppo / Head office</strong> represented in this Contract by <strong>Mr. Fredy Youssef</strong>'"
        :ar="$G('<strong>مركز الرجاء المسيحي - حلب / الإدارة العامة</strong> الممثل في هذا العقد ب<strong>السيد فريدي يوسف</strong>')"
    />
    <x-contract.dual
        :en="'<strong>Mr. Fredy Youssef</strong> is delegated by the Latin church in Syria to manage The Christian Hope Center and all affiliated programs.'"
        :ar="$G('إن السيد فريدي يوسف تم تفويضه من قبل النيابة الرسولية اللاتينية في سوريا لإدارة مركز الرجاء المسيحي وكل البرامج التابعة له.')"
    />
    <x-contract.dual
        :en="'Hereinafter referred to as “<strong>Christian Hope Center - Aleppo - Head Office</strong>”'"
        :ar="$G('المشار إليه في هذا العقد بـ “<strong>مركز الرجاء المسيحي – حلب - الإدارة العامة</strong>”')"
    />
</div>

<x-contract.dual rowClass="mt-3 fw-bold" :en="'And'" :ar="$G('و')"/>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-1" :en="$L('Second Party:')" :ar="$L($G('الفريق الثاني:'))"/>
    <x-contract.dual :en="'<strong>Ms./Mrs.</strong> '.$esc($nameEn)"
                     :ar="'<strong>'.$G('السيد/ة:').'</strong> '.$esc($nameAr)"/>
    <x-contract.dual :en="'<strong>Date and Place of Birth:</strong> '.$esc($pobEn).' — '.$esc($dobStr)"
                     :ar="'<strong>'.$G('مكان وتاريخ الولادة:').'</strong> '.$esc($pobAr).' — '.$esc($dobStrAr)"/>
    <x-contract.dual :en="'<strong>National ID number:</strong> '.$esc($nationalId)"
                     :ar="'<strong>'.$G('الرقم الوطني:').'</strong> '.$esc($D($nationalId ?? ''))"/>
    <x-contract.dual :en="'<strong>Nationality:</strong> '.$esc($natEn)"
                     :ar="'<strong>'.$G('الجنسية:').'</strong> '.$esc($natAr)"/>
    <x-contract.dual :en="'<strong>Address:</strong> '.$esc($addressEn)"
                     :ar="'<strong>'.$G('العنوان:').'</strong> '.$esc($addressAr)"/>
    <x-contract.dual :en="'Hereinafter referred to as “<strong>Volunteer</strong>”'"
                     :ar="$G('المشار إليه في هذا العقد بـ “<strong>المتطوع</strong>”')"/>
</div>

<div class="avoid-break">
    <x-contract.dual rowClass="mt-4" :en="$L('Preamble:')" :ar="$L($G('المقدمة:'))"/>
    <x-contract.dual
        :en="'Whereas the Volunteer has stated that he/she possesses the required qualifications for <strong>MEAL Manager</strong> (the qualifications are attached to the job description), and on that basis has offered to volunteer for “Christian Hope Center Aleppo / Head office” as a <strong>MEAL Manager</strong>, starting from the signing date of this contract.'"
        :ar="$G('بما أن المتطوع قد ذكر أنه يتمتع بالمؤهلات المناسبة ل<strong>مسؤول متابعة وتقييم ومساءلة وتعلم</strong> (المؤهلات مرفقة بتوصيف العمل التطوعي)، وعلى ذلك فقد تقدم للتطوع لدى “مركز الرجاء المسيحي - حلب - الإدارة العامة” بصفة <strong>مسؤول متابعة وتقييم ومساءلة وتعلم</strong> ابتداءً من تاريخ توقيع هذا العقد.')"
    />
</div>
