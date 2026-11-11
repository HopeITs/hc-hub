<?php

return [
    'titles' => [
        'general' => 'General statistics',
        'contracts' => 'Contract statistics',
        'salaries' => 'Salary statistics',
    ],

    'count' => 'Count',
    'total' => 'Total',
    'empty' => 'Nothing to count yet.',

    'filters' => [
        'city' => 'City',
        'all_cities' => 'All cities',
        'program' => 'Program',
        'all_programs' => 'All programs',
        'clear' => 'Clear filters',
    ],

    'totals' => [
        'active' => 'Active employees',
        'contracts' => 'Running contracts',
        'ending' => 'Ending in 30 days',
        'employees' => 'Employees on record',
    ],

    'general' => [
        'lede' => 'Head counts and contract movement for the governorates you look after. Numbers refresh every ten minutes.',
        'by_department' => 'Employees by department',
        'by_job_level' => 'Employees by job level',
        'by_contract_type' => 'Running contracts by type',
        'no_leaves' => 'Never asked for annual leave',
        'no_leaves_hint' => 'Active employees with an annual leave balance and no leave request yet.',
    ],

    'traffic' => [
        'title' => 'Personnel traffic',
        'period' => 'Period',
        'modes' => [
            'monthly' => 'Last six months',
            'quarterly' => 'This year by quarter',
            'yearly' => 'Last five years',
        ],
        'new' => 'New contracts',
        'ended' => 'Ended contracts',
        'terminated' => 'Terminated contracts',
        'quarter' => 'Q:number',
    ],

    'contracts' => [
        'lede_programs' => 'Running contracts per program',
        'lede_centers' => 'Running contracts per center in :program',
        'employees' => 'Employees',
        'contracts' => 'Contracts',
        'centers' => 'Centers',
        'program' => 'Program',
        'center' => 'Center',
        'groups' => [
            'annual' => 'Annual',
            'service' => 'Service',
            'trial' => 'Trial',
        ],
        'slots' => [
            'full' => 'Full time',
            'part' => 'Part time',
            'online' => 'Online',
            'onsite' => 'Onsite',
        ],
        'empty_filtered' => 'No running contracts match these filters.',
        'show_centers' => 'Show centers',
        'open_contracts' => 'Open contracts',
    ],

    'salaries' => [
        'lede' => 'How people are paid, read on each person\'s newest contract. Numbers refresh every ten minutes.',
        'by_period' => 'Employees by salary type',
        'by_program' => 'Salary type by program',
        'table' => 'Salary type per program',
        'hint' => 'Paid by the month, session or hour.',
        'people' => 'Employees',
        'hidden_title' => 'Pay figures are hidden',
        'hidden_body' => 'Only HR roles may read pay. Switch to an HR role to see these figures.',
    ],
];
