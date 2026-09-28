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

    /*
    |--------------------------------------------------------------------------
    | Screening decisions
    |--------------------------------------------------------------------------
    |
    | A rejection must name one of these reasons. The code is stored on the
    | decision and shown to staff in the history; it never reaches the
    | candidate, and neither does the free-text note. Labels live in
    | recruitment-applicant.decisions.rejection_reasons.{code}. 'other' is the
    | only code that also demands a written note.
    |
    | The rejection email is a notification of a decision already saved, so a
    | queue that is down or a mail server that refuses it never undoes the
    | decision. One email per application, ever.
    |
    */

    'decisions' => [
        'rejection_reasons' => [
            'qualifications_mismatch',
            'insufficient_experience',
            'language_requirement',
            'location_mismatch',
            'incomplete_application',
            'stronger_candidates',
            'position_filled',
            'other',
        ],
        'bulk_limit' => 500,
        'notice_queue' => env('RECRUITMENT_NOTICE_QUEUE', 'default'),
        // A rejection email still unsent after this long is dispatched again
        // by recruitment:rejection-notices (a job that never reached the queue).
        'redispatch_after_minutes' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Interviews (Phase 2 Task 7)
    |--------------------------------------------------------------------------
    |
    | reminder_offsets_minutes: how long before the interview each candidate
    | reminder goes out. One entry per reminder — add 120 for a second one two
    | hours before, no code change needed. A slot fixed after a reminder's
    | window opened gets no reminder for that offset: the scheduling email was
    | just sent.
    |
    | Candidate emails and reminders go through the same queue as the
    | rejection email and, like it, never undo what they describe.
    |
    | cancellation_reasons: labels live in
    | recruitment-applicant.interviews.cancellation_reasons.{code}. They are
    | internal and never reach the candidate. 'application_closed' is used by
    | the system when the application is rejected, withdrawn or archived
    | while an interview is still open; HR cannot pick it.
    |
    */

    'interviews' => [
        'reminder_offsets_minutes' => [1440],
        'notice_queue' => env('RECRUITMENT_NOTICE_QUEUE', 'default'),
        // A queued email still unsent after this long is dispatched again by
        // the reminder sweep (a queue that was down when it was written).
        'redispatch_after_minutes' => 15,
        'min_duration_minutes' => 15,
        'max_duration_minutes' => 480,
        'default_duration_minutes' => 60,
        'max_participants' => 15,
        'cancellation_reasons' => [
            'candidate_declined',
            'candidate_unavailable',
            'interviewer_unavailable',
            'position_on_hold',
            'scheduled_in_error',
            'other',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Interview evaluations (Phase 2 Task 8)
    |--------------------------------------------------------------------------
    |
    | reviewer_roles: which participant roles may write an evaluation. Being
    | on the interview is the grant — no permission is involved.
    |
    | Evaluation opens at the interview's start time and is refused for a
    | cancelled or no-show interview. After completion HR can close it; with
    | auto_close_days set it also closes that many days after the outcome was
    | recorded (null: only HR closes it). Once closed, reviewers can no
    | longer edit; HR with correct-evaluations still can, with a reason.
    |
    */

    'evaluations' => [
        'reviewer_roles' => ['interviewer', 'observer'],
        'auto_close_days' => null,
        'max_text_length' => 5000,
        'min_reason_length' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job offers (Phase 2 Task 9)
    |--------------------------------------------------------------------------
    |
    | validity_days: the default time a sent offer stays open before it
    | expires (HR can pick another date when sending). The expiry sweep runs
    | every fifteen minutes; recording an answer also re-checks the clock.
    |
    | cancellation_reasons: internal, never shown to the candidate.
    | 'application_closed' is the system's own, used when the application is
    | moved back or rejected while a draft offer is still open.
    |
    */

    'offers' => [
        'validity_days' => 7,
        'max_validity_days' => 60,
        'max_probation_days' => 180,
        'max_weekly_hours' => 60,
        'max_salary' => 100000000,
        'cancellation_reasons' => [
            'candidate_withdrew',
            'position_on_hold',
            'another_candidate_chosen',
            'created_in_error',
            'other',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Hiring conversion (Phase 2 Task 10)
    |--------------------------------------------------------------------------
    |
    | The contract takes every term the accepted offer carries. These fill
    | only what an offer does not carry, with the values the contract composer
    | itself starts a new contract from (ContractForm), so a converted hire's
    | contract reads like one HR typed in; HR adjusts the schedule afterwards
    | on the ordinary contract page if needed.
    |
    | weekly_hours: when the offer left weekly hours empty (a service
    | contract, the one category allowed 0, gets 0).
    | term: when the offer left the end date empty — a trial contract runs
    | trial_months, anything else to the end of the year it starts in.
    |
    */

    'conversions' => [
        'weekly_hours' => 40,
        'work_hours_from' => '09:00',
        'work_hours_to' => '17:00',
        'trial_months' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | CV analysis
    |--------------------------------------------------------------------------
    |
    | Runs on a CV only after the antivirus has cleared it, entirely on this
    | server with free tools: pdftotext (poppler-utils) for PDFs, tesseract
    | for scanned PDFs, ZipArchive for DOCX and antiword for old .doc files.
    | Nothing leaves the server and nothing is paid for.
    |
    |   apt install poppler-utils tesseract-ocr tesseract-ocr-ara antiword
    |
    | What it produces is a reading aid, like the matching indicators: the
    | skills, education, languages and experience it found, set against what
    | the recruitment request asked for, with the evidence for each. It never
    | ranks, filters or decides. Only the extracted facts are stored, never
    | the CV's text.
    |
    */

    'cv_analysis' => [
        'enabled' => env('RECRUITMENT_CV_ANALYSIS', true),
        'binaries' => [
            'pdftotext' => env('PDFTOTEXT_BINARY', 'pdftotext'),
            'pdftoppm' => env('PDFTOPPM_BINARY', 'pdftoppm'),
            'tesseract' => env('TESSERACT_BINARY', 'tesseract'),
            'antiword' => env('ANTIWORD_BINARY', 'antiword'),
        ],
        'timeout' => 60,
        // A PDF yielding less text than this is treated as a scan and OCR'd.
        'min_text_chars' => 80,
        'ocr' => [
            'enabled' => env('RECRUITMENT_CV_OCR', true),
            'languages' => 'ara+eng',
            'max_pages' => 3,
            'dpi' => 200,
        ],
        'max_text_chars' => 200000,

        // Bridges between how a request and a CV may name the same skill,
        // across Arabic and English. Every term in a group matches the others.
        'skill_synonyms' => [
            ['excel', 'ms excel', 'microsoft excel', 'إكسل', 'اكسل', 'اكسيل'],
            ['word', 'ms word', 'microsoft word', 'وورد'],
            ['ms office', 'microsoft office', 'office', 'أوفيس', 'اوفيس', 'مايكروسوفت أوفيس'],
            ['powerpoint', 'power point', 'باوربوينت', 'بوربوينت'],
            ['accounting', 'accountant', 'محاسبة', 'محاسب'],
            ['communication', 'communication skills', 'تواصل', 'مهارات التواصل', 'اتصال'],
            ['teamwork', 'team work', 'عمل جماعي', 'العمل ضمن فريق', 'روح الفريق'],
            ['leadership', 'قيادة', 'القيادة'],
            ['report writing', 'reporting', 'كتابة التقارير', 'إعداد التقارير', 'تقارير'],
            ['data entry', 'إدخال البيانات', 'ادخال البيانات'],
            ['project management', 'إدارة المشاريع', 'ادارة المشاريع'],
            ['psychosocial support', 'pss', 'الدعم النفسي', 'دعم نفسي اجتماعي'],
            ['case management', 'إدارة الحالة', 'ادارة الحالات'],
            ['teaching', 'teacher', 'تدريس', 'مدرس', 'معلم'],
            ['driving', 'driving license', 'driver', 'قيادة سيارة', 'شهادة سوق', 'سائق'],
            ['translation', 'translator', 'ترجمة', 'مترجم'],
            ['networking', 'networks', 'شبكات'],
            ['programming', 'software development', 'برمجة'],
            ['first aid', 'الإسعافات الأولية', 'اسعافات اولية'],
            ['procurement', 'purchasing', 'مشتريات', 'المشتريات'],
            ['logistics', 'لوجستيات', 'اللوجستيات'],
            ['monitoring and evaluation', 'm&e', 'meal', 'المراقبة والتقييم', 'الرصد والتقييم'],
        ],

        // language key => words that name it in a CV
        'languages' => [
            'arabic' => ['arabic', 'العربية', 'عربي'],
            'english' => ['english', 'الإنجليزية', 'الانكليزية', 'الإنكليزية', 'انجليزي', 'انكليزي'],
            'french' => ['french', 'الفرنسية', 'فرنسي'],
            'german' => ['german', 'الألمانية', 'ألماني'],
            'turkish' => ['turkish', 'التركية', 'تركي'],
            'kurdish' => ['kurdish', 'الكردية', 'كردي'],
            'armenian' => ['armenian', 'الأرمنية', 'أرمني'],
        ],

        // A date range on a line with one of these words is schooling, not work.
        'education_line_words' => [
            'university', 'college', 'school', 'bachelor', 'master', 'diploma', 'degree', 'faculty', 'institute',
            'جامعة', 'كلية', 'مدرسة', 'ثانوية', 'بكالوريوس', 'إجازة', 'ماجستير', 'دبلوم', 'معهد', 'شهادة',
        ],
    ],

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
