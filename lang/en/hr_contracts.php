<?php

return [
    'titles' => [
        'index' => 'Contracts',
        'show' => 'Contract',
        'form' => 'Contract form',
    ],

    'search' => 'Search contracts',
    'period' => 'Term',
    'auto_renews' => 'Renews automatically',
    'yes' => 'Yes',
    'no' => 'No',

    'views' => [
        'active' => 'Running',
        'ending-soon' => 'Ending soon',
        'ended' => 'Ended',
        'not-renewed' => 'Not renewed',
        'draft' => 'Drafts',
    ],

    'status' => [
        'draft' => 'Draft',
        'terminated' => 'Terminated',
        'active' => 'Active',
        'expired' => 'Expired',
        'inactive' => 'Inactive',
    ],

    'filters' => [
        'all_types' => 'All contract types',
    ],

    'fields' => [
        'employee_id' => 'Employee',
        'position_id' => 'Position',
        'contract_type_id' => 'Contract type',
        'type' => 'Work type',
        'place' => 'Work place',
        'is_active' => 'Active',
        'auto_renew' => 'Renew automatically',
        'start_date' => 'Start date',
        'end_date' => 'End date',
        'terminated_at' => 'Termination date',
        'termination_reason_id' => 'Termination reason',
        'reason_of_termination' => 'Termination notes',
        'weekly_hours' => 'Hours per week',
        'schedule_mode' => 'Schedule',
        'work_hours_from' => 'Work starts',
        'work_hours_to' => 'Work ends',
        'work_hours_2_from' => 'Second shift starts',
        'work_hours_2_to' => 'Second shift ends',
        'work_day_from' => 'First work day',
        'work_day_to' => 'Last work day',
        'show_days_per_week' => 'Days per week',
        'show_hours_per_week' => 'Hours per week',
        'show_hours_per_day' => 'Hours per day',
        'show_day_range' => 'Day range',
        'flex_days_per_week' => 'Days per week',
        'flex_hours_per_week' => 'Hours per week',
        'flex_hours_per_month' => 'Hours per month',
        'flex_varies_by_requirements' => 'Varies with the work',
        'flex_coordinated_with_manager' => 'Agreed with the manager',
        'flex_custom_en' => 'Schedule note (English)',
        'flex_custom_ar' => 'Schedule note (Arabic)',
        'pay_mode' => 'Pay type',
        'currency' => 'Currency',
        'salary' => 'Pay',
        'salary_per' => 'Paid per',
        'session_duration' => 'Session length',
        'pay_tiers' => 'Pay tiers',
        'condition_en' => 'Condition (English)',
        'condition_ar' => 'Condition (Arabic)',
        'amount' => 'Amount',
    ],

    'form' => [
        'create_title' => 'New contract',
        'edit_title' => 'Edit contract',
        'renew_title' => 'Renew contract',
        'identity' => 'Contract',
        'schedule' => 'Schedule',
        'pay' => 'Pay',
        'pay_hidden' => 'Pay is not shown to your role. Saving keeps the pay already on file.',
        'end_date_help' => 'A contract runs at most one year.',
        'show_in_clause' => 'Show in the contract text',
    ],

    'schedule' => [
        'fixed' => 'Fixed hours',
        'flexible' => 'Flexible hours',
        'days' => 'Work days',
        'hours' => 'Work hours',
        'custom' => 'Schedule note',
    ],

    'days' => [
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
    ],

    'actions' => [
        'add' => 'Add contract',
        'save' => 'Save contract',
        'edit' => 'Edit',
        'renew' => 'Renew',
        'terminate' => 'Terminate',
        'print' => 'Print',
        'open_employee' => 'Employee profile',
        'add_tier' => 'Add tier',
        'stop_auto_renew' => 'Stop',
        'start_auto_renew' => 'Turn on',
    ],

    'toasts' => [
        'saved' => 'Contract saved.',
        'terminated' => 'Contract terminated.',
        'deleted' => 'Contract deleted.',
        'delete_blocked' => 'This contract has linked records (leave balances, amendments) and cannot be deleted.',
        'auto_renew_changed' => 'Automatic renewal updated.',
    ],

    'confirm' => [
        'delete_title' => 'Delete this contract?',
        'delete_body' => 'The contract is removed and its seat on the position is freed. This cannot be undone.',
    ],

    'errors' => [
        'position_full' => 'This position has no free seat.',
        'employee_incomplete' => 'The employee record is a draft. Complete it before adding a contract.',
        'service_requires_freelance' => 'A service contract must be freelance.',
        'freelance_requires_service' => 'Freelance is only for service contracts.',
        'weekly_hours_required' => 'Enter the weekly hours (only service contracts may leave them at 0).',
        'pay_hidden_new' => 'Your role cannot set pay, so it cannot create a contract from scratch. Ask HR.',
        'cannot_auto_renew' => 'A terminated contract or a trial cannot renew automatically.',
    ],

    'empty' => [
        'heading' => 'No contracts found',
        'body' => 'Contracts in this view will appear here.',
    ],

    'print' => [
        'title' => 'Employment contract',
        'employee' => 'Employee',
        'employee_signature' => 'Employee signature',
        'employer_signature' => 'For Hope Center',
    ],
];
