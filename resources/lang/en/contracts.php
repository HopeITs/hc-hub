<?php

/*
 * Contracts area: list table, composer (create/edit), renewal and the
 * on-screen contract viewer's chrome. The contract document itself (the
 * bilingual clauses under livewire/contract/details/*) is not translated
 * through here: it prints both languages side by side by design.
 */

return [
    'contract' => 'Contract',
    'edit-contract' => 'Edit contract',

    // Contract viewer pager (screen only; the PDF has its own footer).
    'pager-label' => 'Contract pages',
    'page-of' => 'Page :page of :total',
    'page-short' => ':page of :total',
    'go-to-page' => 'Page :page',

    // List table status column.
    'status-terminated' => 'Terminated',
    'status-expired' => 'Expired',

    // Counts. Decimal variants are used for fractional values (e.g. 2.5 months).
    'months-count' => '{1} :count month|[0,*] :count months',
    'months-decimal' => ':count months',
    'days-count' => '{1} :count day|[0,*] :count days',
    'days-decimal' => ':count days',
    'hours-count' => '{1} :count hour|[0,*] :count hours',
    'hours-decimal' => ':count hours',
    'hours-short' => ':count h',
    'tiers-count' => '{0} No conditions|{1} :count condition|[2,*] :count conditions',

    // Composer: labels next to the "include in printed contract" checkboxes.
    'include-days-per-week' => '{1} Include ":count day/week" in the printed contract|[0,*] Include ":count days/week" in the printed contract',
    'include-hours-per-week' => '{1} Include ":count hour/week" in the printed contract|[0,*] Include ":count hours/week" in the printed contract',
    'include-hours-per-day' => '{1} Include ":count hour/day" in the printed contract|[0,*] Include ":count hours/day" in the printed contract',

    'amount' => 'Amount',

    // Server-side checks raised by the composer.
    'errors' => [
        'city-not-allowed' => 'You may only manage contracts in your active city.',
        'employee-city-mismatch' => 'The selected employee must belong to the contract city.',
        'position-not-found' => 'The selected position no longer exists.',
        'position-not-in-city' => 'The selected position is not in the contract city.',
        'position-program-mismatch' => 'The selected position does not belong to the selected program.',
        'position-center-mismatch' => 'The selected position does not belong to the selected center.',
        'position-full' => 'This position has no free seats left.',
    ],

    // Validation attribute names for ContractForm.
    'attributes' => [
        'employee_id' => 'employee',
        'position_id' => 'position',
        'contract_type_id' => 'contract type',
        'type' => 'type',
        'place' => 'place',
        'is_active' => 'status',
        'start_date' => 'start date',
        'end_date' => 'end date',
        'terminated_at' => 'termination date',
        'termination_reason_id' => 'termination reason',
        'reason_of_termination' => 'termination note',
        'weekly_hours' => 'weekly hours',
        'schedule_mode' => 'schedule',
        'work_day_from' => 'from day',
        'work_day_to' => 'to day',
        'work_hours_from' => 'work hours from',
        'work_hours_to' => 'work hours to',
        'work_hours_2_from' => 'second shift from',
        'work_hours_2_to' => 'second shift to',
        'flex_days_per_week' => 'days per week',
        'flex_hours_per_week' => 'hours per week',
        'flex_hours_per_month' => 'hours per month',
        'flex_custom_en' => 'custom description (EN)',
        'flex_custom_ar' => 'custom description (AR)',
        'pay_mode' => 'pay mode',
        'currency' => 'currency',
        'salary' => 'salary',
        'salary_per' => 'salary per',
        'session_duration' => 'session duration',
        'pay_tiers' => 'pay conditions',
        'pay_tiers.*.condition_en' => 'condition (EN)',
        'pay_tiers.*.condition_ar' => 'condition (AR)',
        'pay_tiers.*.amount' => 'amount',
    ],
];
