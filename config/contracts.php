<?php

return [

    /*
    | Contract type ids (contract_types.id) mapped to the category the HR
    | rules depend on: trial contracts, service agreements and annual ones.
    */
    'map_by_id' => [
        1 => 'annual',
        3 => 'trial',
        5 => 'service',
    ],

    /*
    | An auto-renewing contract that ends in one of these months is renewed on
    | 1 January for the whole new year, not the day after it ends (which made
    | renewals of a few days). City HR is told when it ends. A contract ending
    | on 31 December renews on 1 January either way.
    */
    'renew_at_new_year_months' => [10, 11, 12],

    /*
    | The first party's signatory on the printed contracts and amendments.
    | name_*: the signature block. representative_*: the "Represented by ..."
    | line on page 1, honorific included. Kept in .env, never in the views;
    | empty prints a blank line to sign over.
    */
    'signatory' => [
        'name_en' => env('CONTRACT_SIGNATORY_NAME_EN', ''),
        'name_ar' => env('CONTRACT_SIGNATORY_NAME_AR', ''),
        'representative_en' => env('CONTRACT_SIGNATORY_REPRESENTATIVE_EN', ''),
        'representative_ar' => env('CONTRACT_SIGNATORY_REPRESENTATIVE_AR', ''),
    ],
];
