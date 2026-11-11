@props(['contract'])

{{--
    The stipend clause of the printed contract and the amendment sheet, in
    English and Arabic side by side, worded from pay_mode, salary_per,
    currency and the pay tiers (read from the contract's payTiers relation,
    so load it). Ported from HR's <x-contract.pay-clause>; the wording is the
    contract's legal text, printed in both languages at once. Both print
    routes require pay access (ContractPolicy::export); a viewer without it
    still gets the clause withheld.
--}}
@php
    $sessionDurationLabels = [
        \Modules\Hr\Enums\SessionDuration::Hour->value => ['en' => 'one hour', 'ar' => 'ساعة واحدة'],
        \Modules\Hr\Enums\SessionDuration::HourAndHalf->value => ['en' => 'an hour and a half', 'ar' => 'ساعة ونصف'],
        \Modules\Hr\Enums\SessionDuration::TwoHours->value => ['en' => 'two hours', 'ar' => 'ساعتين'],
    ];

    $currency = (int) $contract->currency;
    $currencyLabels = \Modules\Hr\Enums\Currency::getLocalizedName($currency);
    $currencyEn = $currencyLabels['en'];
    $currencyAr = $currencyLabels['ar'];

    $formatAmount = fn (mixed $amount): string => $amount !== null && $amount !== '' ? number_format((float) $amount, 0, '.', ',') : '—';

    // A foreign-currency contract carries an SYP-equivalent-in-cash clause.
    $needsSypEquivalent = in_array($currency, [\Modules\Hr\Enums\Currency::USD->value, \Modules\Hr\Enums\Currency::EUR->value], true);

    $rows = [];

    if (auth()->check() && ! \Modules\Hr\Support\Employees\HrAccess::canViewSalary(auth()->user())) {
        $rows[] = ['en' => '—', 'ar' => '—'];
    } elseif ((int) $contract->pay_mode === \Modules\Hr\Enums\PayMode::Flexible->value) {
        $tiers = $contract->payTiers;

        if ($tiers->isEmpty()) {
            $rows[] = ['en' => '—', 'ar' => '—'];
        }

        foreach ($tiers as $tier) {
            $amount = $formatAmount($tier->amount);

            $rows[] = [
                'en' => '- '.e(trim((string) $tier->condition_en)).': <strong>'.$amount.' '.$currencyEn.'</strong>.',
                'ar' => '- '.e(trim((string) $tier->condition_ar)).': <strong>'.$amount.' '.$currencyAr.'</strong>.',
            ];
        }

        if ($needsSypEquivalent) {
            $rows[] = [
                'en' => 'Or the equivalent amount in Syrian pounds, paid in cash to the volunteer at the end of each month.',
                'ar' => 'أو ما يعادل ذلك بالليرة السورية، تُمنح نقداً للمتطوع في نهاية كل شهر.',
            ];
        }
    } else {
        $amount = $formatAmount($contract->salary);
        $amountEn = $amount.' '.$currencyEn;
        $amountAr = $amount.' '.$currencyAr;
        $salaryPer = (int) $contract->salary_per;

        if ($salaryPer === \Modules\Hr\Enums\SalaryPer::Hour->value) {
            $stipendEn = 'Christian Hope Center is to give an hourly stipend of <strong>/ '.$amountEn.' /</strong> per hour of work.';
            $stipendAr = 'خصص الفريق الأول للفريق الثاني مكافأة بواقع <strong>/ '.$amountAr.' /</strong> عن كل ساعة عمل.';
        } elseif ($salaryPer === \Modules\Hr\Enums\SalaryPer::Session->value) {
            $duration = $sessionDurationLabels[(int) $contract->session_duration] ?? ['en' => '—', 'ar' => '—'];
            $stipendEn = 'Christian Hope Center is to give a stipend of <strong>/ '.$amountEn.' /</strong> per session, each session being '.$duration['en'].'.';
            $stipendAr = 'خصص الفريق الأول للفريق الثاني مكافأة وقدرها <strong>/ '.$amountAr.' /</strong> عن كل جلسة، مدة الجلسة الواحدة '.$duration['ar'].'.';
        } else {
            $stipendEn = 'Christian Hope Center is to give a monthly stipend of <strong>/ '.$amountEn.' /</strong>';
            $stipendAr = 'خصص الفريق الأول للفريق الثاني شهرياً مكافأة وقدرها<br><strong>/ '.$amountAr.' /</strong>';
        }

        $rows[] = ['en' => $stipendEn, 'ar' => $stipendAr];

        if ($needsSypEquivalent) {
            $rows[] = [
                'en' => 'Or its equivalent in Syrian pounds, to be paid in cash to the volunteer at the end of each month.',
                'ar' => 'أو ما يعادلها بالليرة السورية، تُمنح نقداً في نهاية كل شهر.',
            ];
        }
    }
@endphp

@foreach ($rows as $row)
    <x-hr::contract-legal.dual class="mt-1" :en="$row['en']" :ar="$row['ar']" />
@endforeach
