<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Who may raise a recruitment request
    |--------------------------------------------------------------------------
    |
    | Eligibility comes from where someone sits in the org chart, not from a
    | role an administrator hands out: a promotion qualifies them the same day,
    | and moving off a management position withdraws it. These are job_levels
    | grades — 1 Program Manager, 3 Project Manager, 5 Department Manager. The
    | CEO sits at grade 1, so he is already covered and is not listed again.
    |
    | Grade eligibility alone does not authorize a normal request. D50 restricts
    | that path to active manager/general-manager roles; HR uses Direct Opening
    | only. D49 limits creation to descendants (plus self for a new parent),
    | independently of the one-level visibility scope below.
    |
    */

    'requester_job_level_grades' => [1, 3, 5],

    // D1: Project is Program; these are job-level grades, not role names.
    'program_scoped_creation_grades' => [1, 3],
    'department_manager_grade' => 5,

    /*
    |--------------------------------------------------------------------------
    | Which HR role reviews a governorate's requests
    |--------------------------------------------------------------------------
    |
    | Governorate HR approval is the last mandatory step, so every city needs an
    | answer here — HR-ALP and HR-TAR exist as roles but nobody holds them, and
    | Aleppo alone carries 46 positions. Keys are city slugs; a city that is not
    | listed falls back to 'default'. A request is never left without a
    | reviewer because its governorate has no HR of its own.
    |
    */

    'governorate_hr' => [
        'map' => [
            'ALP' => 'HR-SYR',
            'TAR' => 'HR-LAT',
        ],

        'default' => 'HR-SYR',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions that belong to a person, never to a role
    |--------------------------------------------------------------------------
    |
    | Both of these answer "is this specific human the one?", so attaching
    | either to a role would be a security hole rather than a convenience:
    | every holder of that role would become the financial approver, or would
    | be able to skip manager approval entirely.
    |
    | They are resolved from direct grants only (model_has_permissions), so a
    | role that somehow carries one of them still grants nobody anything.
    | Access Management is where they are handed out.
    |
    */

    'user_level_permissions' => [
        // The organization's single financial approver — today the holder of
        // the Senior Financial Manager position.
        'finance_approver' => 'recruitment-requests.approve-finance',

        // The top of the org chart: people with no manager above them, whose
        // requests therefore skip the manager approval step. Finance and HR
        // approval still apply to them.
        'top_level_requester' => 'recruitment-requests.submit-without-manager',
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles that see every request in the organization
    |--------------------------------------------------------------------------
    |
    | HR Syria and the general management are not steps in the workflow; they
    | watch it. They see every request, its state and its timeline, and only
    | act when they happen to hold an organizational role in that particular
    | request — as the requester's manager, say.
    |
    */

    'organization_wide_roles' => [
        'super-admin',
        'HR-SYR',
        'general-manager',
    ],

    /*
    |--------------------------------------------------------------------------
    | Fixed answers on the request form
    |--------------------------------------------------------------------------
    |
    | The paper form offers these as tick boxes, so the screen offers exactly
    | the same choices rather than a free-text field that nobody can report on.
    |
    | They live here as slugs rather than as numeric enums because their
    | columns — recruitment_reason, priority, volunteer_type — are already text
    | in the recruitment migrations, and changing that would mean a migration.
    | The slug is what is stored; the label is translated at display time, so
    | the database never holds a sentence in one language.
    |
    */

    'request_form' => [

        // Why this person is being recruited. 'other' reveals a free-text
        // field that is stored in the other_reason column beside it.
        'recruitment_reasons' => [
            'operational_need',
            'new_position',
            'replacement',
            'other',
        ],

        'priorities' => [
            'urgent',
            'high',
            'normal',
        ],

        // Where the work is done.
        'volunteer_types' => [
            'onsite',
            'remote',
        ],

        /*
        | A new position must name the centre it sits in, with two exceptions.
        |
        | Remote work has no centre to name. And these programmes are run
        | across many centres rather than out of one, so pinning a position to
        | a single centre would be a fiction. Matched on programs.slug.
        */
        'centerless_program_slugs' => [
            'SZ',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Applicant tags
    |--------------------------------------------------------------------------
    |
    | Tags are internal labels for HR, never shown to an applicant. The
    | palette is a closed list so a colour is a choice, not free input, and
    | Tailwind can see every class. bulk_limit caps one bulk operation; a
    | larger set is narrowed with filters first.
    |
    */

    'tags' => [
        'scope_key' => 'organization',
        'min_length' => 2,
        'max_length' => 40,
        'bulk_limit' => 500,
        'palette' => [
            'neutral' => 'bg-neutral-100 text-neutral-800',
            'red' => 'bg-red-50 text-red-800',
            'amber' => 'bg-amber-50 text-amber-800',
            'green' => 'bg-green-50 text-green-800',
            'blue' => 'bg-blue-50 text-blue-800',
            'purple' => 'bg-purple-50 text-purple-800',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Matching indicators
    |--------------------------------------------------------------------------
    |
    | The criteria RecruitmentMatchingIndicators reads. Every indicator is a
    | rule written here, not a learned weight, so the screen can say exactly
    | why it shows what it shows. Changing anything below changes the
    | criteria version (a hash of this array), and saved snapshots keep the
    | version they were computed under.
    |
    | Answers are read by form field key. A form version that does not ask a
    | question produces "not assessed", never a miss.
    |
    */

    'indicators' => [
        'fields' => [
            'preferred_city' => 'vacancy_city',
            'residence' => 'city',
            'experience_years' => 'experience_years',
            'education_level' => 'education_level',
            'experience_field' => 'experience_field',
            'experience_text' => 'experience',
        ],

        // vacancy_city option value => cities.slug
        'city_answers' => [
            'aleppo' => 'ALP',
            'homs' => 'HMS',
            'damascus' => 'DMS',
            'latakia' => 'LAT',
            'tartus' => 'TAR',
        ],

        // experience_years option value => [min, max] years; null max is open-ended.
        'experience_ranges' => [
            'less_than_1' => [0, 0],
            '1_3' => [1, 3],
            '3_5' => [3, 5],
            '5_plus' => [5, null],
        ],

        // education_level option value => rank; higher is more advanced.
        'education_ranks' => [
            'institute' => 1,
            'university' => 2,
            'master' => 3,
            'doctorate' => 4,
        ],

        // Words in a request's free-text required_qualifications that name a
        // level. The lowest level mentioned is taken as the minimum.
        'qualification_keywords' => [
            'institute' => ['institute', 'diploma', 'معهد', 'دبلوم'],
            'university' => ['university', 'bachelor', 'degree', 'graduate', 'جامعة', 'جامعي', 'إجازة', 'بكالوريوس'],
            'master' => ['master', 'ماجستير'],
            'doctorate' => ['doctorate', 'phd', 'ph.d', 'دكتوراه'],
        ],

        // experience_field answers that mean "no experience in the field".
        'no_experience_answers' => ['none', 'no'],
    ],

];
