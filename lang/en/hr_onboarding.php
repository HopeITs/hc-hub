<?php

declare(strict_types=1);

// The pages of the extended onboarding journey.
return [
    'manager_session' => [
        'title' => 'Orientation session with the new volunteer',
        'lead' => 'Your session with :name: set the date, then record it once it has happened.',
        'agenda_intro' => 'What the session covers',
        'agenda_programs' => 'The programs the volunteer will work in.',
        'agenda_workflow' => 'How work runs day to day.',
        'date' => 'Session date',
        'scheduled' => 'Session date saved.',
        'done' => 'Session marked as held.',
        'done_on' => 'Held and recorded on :date.',
    ],
    'policies' => [
        'title' => 'Policies to read and agree to',
        'lead' => 'Read each document, then confirm that you have read it.',
        'sections' => [
            'policy' => 'Policies',
            'review' => 'Documents to review',
        ],
        'agreed' => 'Done',
        'acknowledge' => [
            'policy' => 'I have read it and agree',
            'review' => 'I have reviewed it',
        ],
        'acknowledged' => 'Thank you, this is recorded.',
        'open_source' => 'Open the original document',
        'none' => 'Nothing is required yet.',
    ],
    'required_policies' => [
        'title' => 'Required policies',
        'lead' => 'Mark the documents every new volunteer must go through, and say whether it is a policy to agree to or a document made by another team that is only reviewed (for example the Media guideline).',
        'required' => 'Required for onboarding',
        'kinds' => [
            'policy' => 'Policy (read and agree)',
            'review' => 'Review only',
        ],
        'saved' => 'Saved.',
        'none' => 'No policy documents yet. They come from the policies folder sync.',
    ],
    'inbox' => [
        'open_manager' => 'Open the session',
        'manager_schedule' => 'Schedule your orientation session with the new volunteer',
        'manager_record' => 'Record your orientation session with the new volunteer',
        'electronics' => 'Hand over the electronics',
        'other_items' => 'Hand over the remaining items',
        'mug' => 'Hand over the mug (the trial is over)',
        'policies' => 'Read the policies and agree to them',
        'guideline' => 'Review the Media guideline',
    ],
];
