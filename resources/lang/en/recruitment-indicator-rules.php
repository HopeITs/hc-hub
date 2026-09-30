<?php

return [
    'title' => 'Matching rules',
    'navigation' => 'Matching rules',
    'hint' => 'These rules decide what “met” means in the matching indicators shown on every application. They are an aid for the reviewer, not a decision. Saving starts a new criteria version; snapshots saved earlier keep the version they were computed under.',
    'version' => 'Current criteria version: :version',
    'customised' => 'Customised. Anything left untouched follows the system defaults.',
    'defaults' => 'Using the system defaults.',

    'criteria_title' => 'Criteria in use',
    'criteria_hint' => 'A switched-off criterion disappears from the list and from the “n of m” count.',
    'not_scored' => 'shown for information only, never counted',

    'experience_title' => 'Required experience',
    'experience_hint' => 'How each answer on the application form is read as a range of years. An answer counts as met when its lowest value reaches the years the request requires, and as partly met when its range straddles them.',
    'answer' => 'Form answer',
    'min_years' => 'From (years)',
    'max_years' => 'To (years)',
    'open_ended' => 'leave empty for no upper limit',
    'range_options' => [
        'less_than_1' => 'Less than 1 year',
        '1_3' => '1 to 3 years',
        '3_5' => '3 to 5 years',
        '5_plus' => '5 years or more',
    ],
    'default_experience' => 'Minimum years when the request names none',
    'default_experience_hint' => 'Empty means the criterion is not applicable for requests without a stated requirement, as before.',

    'qualification_title' => 'Qualification',
    'qualification_hint' => 'The level a request needs is read from these words in its “required qualifications” text. When it names several levels, the lowest one is the minimum. Separate words with commas.',
    'default_qualification' => 'Minimum level when the request names none',
    'default_qualification_hint' => 'Empty means the criterion is not assessed when the request does not name a level.',
    'no_default' => 'None (do not assess)',
    'keywords' => 'Words that name :level',

    'save' => 'Save rules',
    'saved' => 'Rules saved. New criteria version: :version.',
    'reset' => 'Restore system defaults',
    'reset_confirm' => 'Discard every saved change and go back to the system defaults?',
    'reset_done' => 'The system defaults are back in force.',
    'form_hint' => 'Changes apply the next time an applicant is opened; saved scores are refreshed with the “recruitment:indicators:snapshot --stale” command.',

    'errors' => [
        'unknown_indicator' => 'One of the criteria is not recognised.',
        'years' => 'Enter a whole number of years between 0 and 50, or leave it empty.',
        'level' => 'Choose one of the listed levels.',
        'range' => 'Enter whole years from 0 to 50; the upper limit, if given, cannot be below the lower one.',
        'keywords' => 'Use at most 30 words, each at most 60 characters.',
    ],
];
