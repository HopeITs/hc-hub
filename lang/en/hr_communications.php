<?php

declare(strict_types=1);

return [
    'titles' => [
        'inbox' => 'Waiting on you',
        'feedback' => 'Feedback',
        'activity' => 'HR activity archive',
        'assistant' => 'Ask HR',
    ],

    'inbox' => [
        'search' => 'Search people or items',
        'kind' => 'Kind',
        'kinds' => [
            'all' => 'Everything',
            'decide' => 'Leaves to decide',
            'review' => 'Leaves to review',
            'checklist' => 'Checklist items',
            'profile' => 'Profile changes',
            'journey' => 'Open journeys',
        ],
        'action' => [
            'decide' => 'Decide leave',
            'review' => 'Review leave',
            'deliver' => 'Hand over item',
            'receive' => 'Take back item',
            'finalize' => 'Close item',
            'approve' => 'Approve change',
            'onboarding' => 'Onboarding',
            'renewal' => 'Renewal',
            'exit' => 'Exit',
        ],
        'journey' => [
            'onboarding' => 'Onboarding still open',
            'renewal' => 'Contract renewal still open',
            'exit' => 'Exit still open',
        ],
        'profile_section' => 'Profile change: :section',
        'columns' => [
            'who' => 'Who',
            'action' => 'To do',
            'what' => 'What',
            'when' => 'Dates',
            'since' => 'Waiting since',
        ],
        'open' => 'Open',
        'started' => 'Already started',
        'empty' => 'Nothing is waiting on you.',
        'empty_filtered' => 'Nothing matches these filters.',
    ],

    'feedback' => [
        'intro' => 'Tell the IT team about a problem, an idea or a question. They read every note.',
        'category' => 'Kind',
        'categories' => [
            'problem' => 'Problem',
            'idea' => 'Idea',
            'question' => 'Question',
        ],
        'all_categories' => 'All kinds',
        'message' => 'Your note',
        'privacy' => 'Sent with the page you came from, your language and your role. Deleted after a year.',
        'send' => 'Send',
        'sent' => 'Thank you, your note was sent.',
        'too_many' => 'You have sent several notes this hour. Please try again later.',
        'received' => 'Notes received',
        'none' => 'No notes yet.',
        'remove_confirm' => 'Delete this note?',
        'columns' => [
            'from' => 'From',
            'context' => 'Page, language, role',
            'when' => 'Sent',
        ],
    ],

    'activity' => [
        'intro' => 'Changes recorded by the old HR app, read only.',
        'search' => 'Search descriptions and values',
        'area' => 'Area',
        'all_areas' => 'All areas',
        'event' => 'Change',
        'all_events' => 'All changes',
        'events' => [
            'created' => 'Added',
            'updated' => 'Changed',
            'deleted' => 'Deleted',
        ],
        'causer' => 'By',
        'anyone' => 'Anyone',
        'from' => 'From',
        'to' => 'To',
        'system' => 'System',
        'empty' => 'No recorded changes match.',
        'fields' => '{1} 1 field|[2,*] :count fields',
        'columns' => [
            'when' => 'When',
            'who' => 'By',
            'what' => 'What changed',
        ],
    ],

    'assistant' => [
        'intro' => 'Ask about your own leave balance or the coming holidays. Answers come from AI and can be wrong; HR has the final word.',
        'question' => 'Your question',
        'privacy' => 'Your leave balances and the coming holidays are sent with the question.',
        'ask' => 'Ask',
        'thinking' => 'Working on an answer…',
        'failed' => 'No answer this time. Please try again, or ask the HR team.',
        'too_many' => 'You have asked many questions this hour. Please try again later.',
    ],
];
