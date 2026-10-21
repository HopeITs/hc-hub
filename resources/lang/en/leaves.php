<?php

return [

    // Leave request workflow status: one term per status, used everywhere.
    'status' => [
        'pending' => 'Pending',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
    ],

    // Unit of a leave request / balance adjustment (radio options, table type column).
    'unit' => [
        'legend' => 'Leave unit',
        'day' => 'Day',
        'hour' => 'Hour',
    ],

    // Leave type eligibility labels (App\Enums\LeaveType).
    'gender' => [
        'any' => 'All',
        'male' => 'Male',
        'female' => 'Female',
    ],

    'marital' => [
        'any' => 'All',
        'single' => 'Single',
        'married' => 'Married',
    ],

    'select-employee' => 'Select employee…',

    // Separator between the start and end of a period ("from → to").
    'range-separator' => '→',

    // Counted durations. Always pass the number as :count.
    'duration' => [
        'days' => '{0} :count days|{1} :count day|[2,*] :count days',
        'days-fraction' => ':count days',
        'hours' => '{0} :count hours|{1} :count hour|[2,*] :count hours',
        'minutes' => '{0} :count minutes|{1} :count minute|[2,*] :count minutes',
        'and' => ':first and :second',
    ],

    'validation' => [
        'start-hour-within-working-hours' => 'The start time must be within the center\'s working hours.',
        'end-hour-within-working-hours' => 'The end time must be within the center\'s working hours.',
        'end-hour-after-start-hour' => 'The end time must be after the start time.',
        'max-hours' => 'The duration must not exceed :max.',
        'max-days' => 'The total leave duration (:days) exceeds the allowed maximum (:max).',
    ],

];
