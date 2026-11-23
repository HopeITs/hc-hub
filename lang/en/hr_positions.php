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
        'open_vacancy' => 'Open vacancy',
        'open_vacancy_name' => 'Open a vacancy for :name',
        'add' => 'Add position',
        'edit' => 'Edit position',
        'toggle_name' => 'Activate or deactivate :name',
    ],

    'critical' => [
        'title' => 'Critical positions',
        'critical' => 'Critical',
        'mark' => 'Mark as critical',
        'unmark' => 'Remove critical mark',
        'marked' => 'The position is now critical.',
        'unmarked' => 'The position is no longer critical.',
        'is_critical_help' => 'This position is critical: HR is alerted when it has no holder.',
        'not_critical_help' => 'Mark this position as critical so HR is alerted when it has no holder.',
        'none_title' => 'No critical positions',
        'none_help' => 'Positions marked as critical in your scope appear here.',
        'holders' => 'Holders',
        'needs_replacement' => 'Needs a replacement',
        'suggested_replacements' => 'Suggested replacements',
        'panel_title' => 'Suggested replacements',
        'suggestions_only' => 'These are suggestions only. Nothing is promoted or hired from this page.',
        'reason' => [
            'vacant' => 'This position is vacant.',
            'contract_ended' => "The last holder's contract has ended and nobody holds the position now.",
        ],
        'internal_title' => 'From inside the organization',
        'internal_empty' => 'No suitable volunteer found',
        'internal_empty_help' => 'Nobody in your scope reaches the minimum score yet.',
        'score' => 'Score :score',
        'open_profile' => 'Open profile',
        'reasons' => [
            'level' => 'Job level :level (+:points)',
            'title' => 'Holds a similar position: :title (+:points)',
            'background' => 'Education, courses or experience match: :matched (+:points)',
            'service' => ':years year(s) in the organization (+:points)',
            'department' => 'Same department (+:points)',
            'same_city' => 'Same city (+:points)',
            'transfer' => 'Would need to transfer to another city',
        ],
        'cv_title' => 'From earlier CVs',
        'cv_empty' => 'Waiting for CVs to be uploaded',
        'cv_empty_help' => 'No earlier applicant who reached the final stages is available to you.',
        'open_candidate' => 'Open candidate file',
        'cv' => [
            'reached' => 'Reached the stage: :stage',
            'same_position' => 'Applied for this same position',
            'similar_position' => 'Applied for a similar position: :position',
        ],
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
        'email_team' => 'Email the team',
        'email_team_directly' => 'Email the team directly',
        'request_hire' => 'Request hire',
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
