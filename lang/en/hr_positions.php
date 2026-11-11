<?php

return [
    'titles' => [
        'index' => 'Positions',
        'team' => 'Team chart',
    ],

    'search' => 'Search positions',
    'search_placeholder' => 'Position title',
    'reports_to' => 'reports to :title',

    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'all' => 'All',
    ],

    'filters' => [
        'any_seats' => 'Any seats',
        'open_seats' => 'With a free seat',
    ],

    'fields' => [
        'title' => 'Position',
        'title_en' => 'Title (English)',
        'title_ar' => 'Title (Arabic)',
        'program_id' => 'Program',
        'city_id' => 'City',
        'center_id' => 'Center',
        'department_id' => 'Department',
        'job_level_id' => 'Job level',
        'direct_manager_id' => 'Direct manager',
        'tech_manager_id' => 'Technical manager',
        'qualifications' => 'Qualifications',
        'responsibilities' => 'Responsibilities',
        'max_emp' => 'Seats',
        'seats' => 'Seats',
        'is_active' => 'Active',
    ],

    'form' => [
        'no_center' => 'No center (online)',
        'no_manager' => 'No manager',
    ],

    'actions' => [
        'add' => 'Add position',
        'edit' => 'Edit position',
        'toggle_name' => 'Activate or deactivate :name',
    ],

    'toasts' => [
        'saved' => 'Position saved.',
        'deleted' => 'Position deleted.',
        'delete_blocked' => 'This position has contracts or reports and cannot be deleted. Deactivate it instead.',
        'status_changed' => 'Position status changed.',
    ],

    'confirm' => [
        'delete_title' => 'Delete :name?',
        'delete_body' => 'The position is removed. This cannot be undone.',
        'toggle_title' => 'Change the status of :name?',
        'toggle_body' => 'An inactive position is not offered for new contracts. Current contracts stay.',
    ],

    'errors' => [
        'city_scope' => 'You can only manage positions in your cities.',
        'center_city' => 'The center must be in the position\'s city.',
    ],

    'empty' => [
        'heading' => 'No positions found',
        'body' => 'Positions in your cities will appear here.',
    ],

    'team' => [
        'positions' => 'Positions',
        'open_seats' => 'Open seats',
        'scope' => 'Show',
        'my_team' => 'My team',
        'whole' => 'Whole organisation',
        'open_only' => 'Only branches with open seats',
        'open_count' => '{1} :count open seat|[0,*] :count open seats',
        'reports' => '{1} :count report|[0,*] :count reports',
        'empty_heading' => 'Nothing to show',
        'empty_body' => 'No positions match this view.',
    ],
];
