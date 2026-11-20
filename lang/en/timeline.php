<?php

// An employee's timeline (roadmap 4.7): dated facts from the lasting records.
return [
    'tab' => 'Timeline',
    'title' => 'Timeline',
    'subtitle' => 'Contracts, projects, equipment and journeys, newest first.',
    'empty' => 'Nothing is recorded for this person yet.',
    'undated' => 'Date not recorded',
    'sources' => [
        'contracts' => 'Contracts',
        'projects' => 'Projects',
        'equipment' => 'Equipment',
        'journeys' => 'Journeys',
        'activity' => 'Activity log',
    ],
    'contracts' => [
        'started' => 'Contract started',
        'ended' => 'Contract ended',
        'terminated' => 'Contract terminated',
    ],
    'projects' => [
        'joined' => 'Joined a project',
        'left' => 'Left a project',
    ],
    'equipment' => [
        'received' => 'Received equipment',
        'returned' => 'Returned equipment',
    ],
    'journeys' => [
        'onboarding_started' => 'Onboarding started',
        'onboarding_completed' => 'Onboarding completed',
        'renewal_started' => 'Renewal started',
        'renewal_completed' => 'Renewal completed',
        'offboarding_started' => 'Exit started',
        'offboarding_completed' => 'Exit completed',
    ],
];
