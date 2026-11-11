{{--
    The amendment sheet (ملحق عقد, hr.contracts.amendments.print): one A4 page
    on the contract's letterhead listing only what changed, the clause that
    keeps the rest of the contract in force, and the two signatures. The new
    schedule and pay are worded by the contract's own clause components, fed
    the contract as the amendment makes it ($after, never saved), so the sheet
    never words a term differently from the contract. The bilingual wording is
    legal text printed in both languages at once, so it lives here.
--}}
@php
    $changed = $amendment->changedFields();
    $emp = $contract->employee;

    $nameEn = trim(($emp?->first_name_en ?? '').' '.($emp?->father_name_en ?? '').' '.($emp?->last_name_en ?? '')) ?: '—';
    $nameAr = trim(($emp?->first_name_ar ?? '').' '.($emp?->father_name_ar ?? '').' '.($emp?->last_name_ar ?? '')) ?: '—';
    $female = (int) $emp?->gender === \Modules\Hr\Enums\Gender::Female->value;

    $LB = fn (string $t): string => '<span class="h-label-bold">'.$t.'</span>';

    $scheduleFields = array_diff(\Modules\Hr\Models\ContractAmendment::AMENDABLE_FIELDS, ['type', 'pay_mode', 'session_duration', 'currency', 'salary_per']);
    $scheduleChanged = array_intersect($scheduleFields, $changed) !== [];
    $payChanged = array_intersect(\Modules\Hr\Models\ContractAmendment::PAY_FIELDS, $changed) !== [];
    $typeChanged = in_array('type', $changed, true);

    $typeWords = [
        \Modules\Hr\Enums\ContractType::FullTime->value => ['en' => 'full time', 'ar' => 'دواماً كاملاً'],
        \Modules\Hr\Enums\ContractType::PartTime->value => ['en' => 'part time', 'ar' => 'دواماً جزئياً'],
        \Modules\Hr\Enums\ContractType::Freelance->value => ['en' => 'freelance', 'ar' => 'دواماً حراً'],
    ][(int) $after->type] ?? ['en' => '—', 'ar' => '—'];

    $effective = $amendment->effective_from->format('d/m/Y');

    // Numbered here, not inside the component attributes: Blade evaluates those more than once.
    $number = 0;
    $typeNo = $typeChanged ? ++$number : null;
    $scheduleNo = $scheduleChanged ? ++$number : null;
    $payNo = $payChanged ? ++$number : null;
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fileTitle }}</title>
    @include('hr::print.contract-legal._styles')
    <style>
        html, body { margin: 0; padding: 0; background: #f4f4f5; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .sheet { padding: 8mm 0; }
        .print-bar { position: sticky; top: 0; z-index: 10; display: flex; gap: 8px; justify-content: center; padding: 10px 16px; background: rgba(255, 255, 255, .92); border-bottom: 1px solid #dcddde; font-family: 'somar-regular', sans-serif; }
        .print-bar button, .print-bar a { border-radius: 8px; padding: 6px 14px; font-size: 14px; border: 1px solid #be1f24; text-decoration: none; cursor: pointer; }
        .print-bar button { background: #be1f24; color: #fff; }
        .print-bar a { background: #fff; color: #be1f24; }
        @media print {
            html, body { background: #fff; }
            .sheet { padding: 0; }
            .contract-a4 { height: 297mm; box-shadow: none; border-radius: 0; }
        }
    </style>
</head>
<body>
    <div class="print-bar no-print">
        <button type="button" onclick="window.print()" data-test="print-amendment-button">{{ __('hr_contracts.actions.print') }}</button>
        <a href="{{ route('hr.contracts.amendments.index', $contract->id) }}">{{ __('hr_contract_amendments.actions.list') }}</a>
    </div>

    <div class="sheet">
        <div class="contract-a4">
            <div class="contract-body">
                <div class="avoid-break contract-title-center">
                    <div class="dual-row dual-tight">
                        <div class="dual-en fw-title">Contract Amendment</div>
                        <div class="contract-title-separator">|</div>
                        <div class="dual-ar fw-title">تعديل عقد</div>
                    </div>
                </div>

                <div class="avoid-break mt-4">
                    <x-hr::contract-legal.dual
                        :en="'The following amendments were made to the contract of the volunteer <strong>'.e($nameEn).'</strong>:'"
                        :ar="'تم إجراء التعديلات التالية في عقد '.($female ? 'المتطوعة' : 'المتطوع').' <strong>'.e($nameAr).'</strong>:'" />
                </div>

                @if ($typeChanged)
                    <div class="avoid-break mt-4">
                        <x-hr::contract-legal.dual
                            :en="$LB($typeNo.'. Type of work:').' to become <strong>'.$typeWords['en'].'</strong>.'"
                            :ar="$LB($typeNo.'. نوع الدوام:').' ليصبح <strong>'.$typeWords['ar'].'</strong>.'" />
                    </div>
                @endif

                @if ($scheduleChanged)
                    <div class="avoid-break mt-4">
                        <x-hr::contract-legal.dual
                            :en="$LB($scheduleNo.'. Volunteer Work Hours,').' to become:'"
                            :ar="$LB($scheduleNo.'. ساعات العمل التطوعّي،').' لتصبح:'" />
                        <x-hr::contract-legal.schedule-clause :contract="$after" />
                    </div>
                @endif

                @if ($payChanged)
                    <div class="avoid-break mt-4">
                        <x-hr::contract-legal.dual
                            :en="$LB($payNo.'. Stipend,').' to become:'"
                            :ar="$LB($payNo.'. المكافأة المالية،').' لتصبح:'" />
                        <x-hr::contract-legal.pay-clause :contract="$after" />
                    </div>
                @endif

                <div class="avoid-break mt-6">
                    <x-hr::contract-legal.dual
                        :en="'All other clauses of the original contract remain in force, except for the amendments above, starting from <strong>'.$effective.'</strong>.'"
                        :ar="'يحافظ على كافة بنود العقد الأساسي عدا التعديلات السابقة، وذلك ابتداءً من تاريخ <strong><span dir=\'ltr\'>'.$effective.'</span></strong>.'" />
                </div>

                <div class="avoid-break contract-signature">
                    <div class="signature-party signature-party-second">
                        <div class="signature-role">{!! $LB('Second Party') !!} / {!! $LB('الفريق الثاني') !!}</div>
                        <div class="signature-caption">Name and Signature / الاسم والتوقيع</div>
                        <div class="signature-name">
                            <strong>{{ $nameEn }}</strong>
                            <br>
                            <strong>{{ $nameAr }}</strong>
                        </div>
                        <div class="signature-date">Date / التاريخ</div>
                    </div>

                    <div class="signature-party signature-party-first">
                        <div class="signature-role">{!! $LB('First Party') !!} / {!! $LB('الفريق الأول') !!}</div>
                        <div class="signature-caption">Name and Signature / الاسم والتوقيع</div>
                        <div class="signature-name">
                            <strong>Fredy Georges Youssef</strong>
                            <br>
                            <strong>فريدي جرجس يوسف</strong>
                        </div>
                        <div class="signature-date">Date / التاريخ</div>
                    </div>
                </div>
            </div>

            <div class="contract-footer">1 / 1</div>
        </div>
    </div>
</body>
</html>
