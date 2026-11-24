<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Permission catalog
|--------------------------------------------------------------------------
|
| One entry per module. Each permission is seeded as "{module}.{action}".
| Add a module here and run `php artisan permissions:sync` to add the new
| permissions without touching any other file (the RolesAndPermissionsSeeder
| resets every role, so it is for a fresh database only). The catalog is built before
| the returned array so the role baselines below can be derived from it.
*/
$groups = [
    'access-management' => ['manage-users', 'manage-roles', 'manage-permissions'],
    'programs' => ['view', 'create', 'update'],
    'projects' => ['view', 'create', 'update', 'delete'],
    'indicators' => ['view', 'create', 'update', 'delete', 'record'],
    'indicator-library' => ['view', 'create', 'update', 'delete'],
    'results' => ['view', 'create', 'update', 'delete'],
    'risks' => ['view', 'create', 'update', 'delete'],
    'targets' => ['view', 'create', 'update', 'delete'],
    'donors' => ['view', 'create', 'update', 'delete', 'approve'],
    'proposals' => ['view', 'create', 'update', 'delete', 'manage-reviewers', 'correct-status', 'approve'],
    'funding-agreements' => ['view', 'create', 'update', 'delete'],
    'transfers' => ['view', 'create', 'update', 'delete'],
    'strategies' => ['view', 'create', 'update', 'delete', 'approve-unmanaged'],
    'annual-plans' => ['view', 'create', 'update', 'delete', 'review'],
    'reports' => ['view', 'create', 'update', 'delete', 'approve', 'mark-seen', 'reopen', 'aggregate', 'reopen-stand-in'],
    'meal-coverage' => ['manage', 'stand-in'],
    'supervisions' => ['manage'],
    'sectors' => ['view', 'manage'],
    'risk-library' => ['manage'],
    'report-templates' => ['manage'],
    'evidence-types' => ['manage'],
    'narrative-questions' => ['manage'],
    'tasks' => ['view', 'create', 'update', 'delete'],
    'calendar' => ['view'],
    'accounts' => ['view', 'create', 'update', 'delete'],
    'cities' => ['view'],
    'centers' => ['create'],
    'activity-log' => ['view'],
    'notification-settings' => ['manage'],
    'ai' => ['draft', 'view-usage'],
    // HR's attendance, mail lists and message templates (ported 2026-11-17).
    // HR's own roles (HR-*) get these through hub:import-hr; the grant
    // migrations of 2026-11-17 give the built-in roles what the baselines say.
    'attendance' => ['view-own', 'view', 'manage'],
    'mail-lists' => ['manage', 'confirm'],
    'message-templates' => ['manage', 'use'],
];

/*
| HR's own catalog (hub roadmap 4.1). Until now these names existed only
| because hub:import-hr copied them from the old HR database; listing them
| here lets `permissions:sync` create them on an empty database. No built-in
| role baseline below gains any of them: who holds them still comes from the
| HR roles, and Super Admin holds everything. An action listed under a module
| that already exists above is added to that module.
*/
$hrGroups = [
    'access-management' => ['view', 'manage-employees'],
    'statistics' => ['view'],
    'employees' => ['view', 'create', 'update', 'delete', 'export'],
    'contracts' => ['view', 'create', 'update', 'delete', 'export', 'amend'],
    'programs' => ['delete'],
    'centers' => ['view', 'update', 'delete', 'export'],
    'positions' => ['view', 'create', 'update', 'delete', 'export', 'mark-critical', 'view-replacements'],
    'leaves' => ['view-own', 'view-all', 'create', 'update', 'delete', 'decide', 'review', 'manage-balances', 'export'],
    'project-basics' => ['view', 'create', 'update', 'delete', 'export'],
    'news' => ['view', 'create', 'update', 'delete'],
    'badges' => ['view', 'create', 'update', 'delete'],
    'archives' => ['view', 'create', 'update', 'delete', 'share', 'download'],
    'checklists' => ['view-own', 'view-all', 'manage', 'deliver', 'receive'],
    'recruitment-requests' => ['view', 'manage', 'submit', 'cancel', 'approve', 'reject', 'request-changes', 'direct-open', 'export', 'approve-finance', 'submit-without-manager'],
    'recruitment-vacancies' => ['view', 'manage'],
    'recruitment-campaigns' => ['view', 'manage'],
    'recruitment-job-posts' => ['view', 'manage'],
    'recruitment-forms' => ['view', 'manage'],
    'recruitment-links' => ['view', 'manage'],
    'recruitment-indicators' => ['manage'],
    'recruitment-applicants' => [
        'view', 'manage', 'download-cv', 'add-notes', 'delete-notes', 'archive-cv', 'change-status', 'endorse',
        'tag', 'bulk-tag', 'manage-tags', 'accept', 'bulk-reject', 'schedule-interviews', 'view-evaluations',
        'correct-evaluations', 'view-offers', 'manage-offers', 'view-offer-salary', 'convert-hires', 'export',
        'request-erasure',
    ],
];

$hrPermissions = [];

foreach ($hrGroups as $module => $actions) {
    foreach ($actions as $action) {
        $hrPermissions[] = "{$module}.{$action}";
    }

    $groups[$module] = [...($groups[$module] ?? []), ...$actions];
}

$allPermissions = [];

foreach ($groups as $module => $actions) {
    foreach ($actions as $action) {
        $allPermissions[] = "{$module}.{$action}";
    }
}

// Every role views every module (user decision 2026-09-25) — except the
// activity log (Super Admin and General Manager) and the money pages (user
// decision 2026-09-29), which only the finance-facing roles below add back.
$financeViews = ['transfers.view', 'funding-agreements.view', 'accounts.view'];
$fundraisingViews = ['donors.view', 'proposals.view'];
// Other people's timesheets (attendance.view) go to managers and the General
// Manager only; everyone sees their own (attendance.view-own).
$restrictedViews = ['activity-log.view', 'attendance.view', ...$financeViews, ...$fundraisingViews];
$viewEverything = [
    ...array_values(array_filter(
        $allPermissions,
        fn (string $permission): bool => str_ends_with($permission, '.view') && ! in_array($permission, [...$restrictedViews, ...$hrPermissions], true),
    )),
    'attendance.view-own',
];

// HR's own work: running attendance, the mailing lists (confirming a member
// is one person's, granted per user) and the message templates stay with the
// HR roles and Super Admin, as in the HR app; the General Manager only views
// timesheets.
$hrOnly = ['attendance.manage', 'mail-lists.manage', 'mail-lists.confirm', 'message-templates.manage', 'message-templates.use'];

// Two-factor enforcement fails closed: only an explicit false/0/off/no turns
// it off; unset, empty or unrecognised values keep it on.
$twoFactorSetting = env('AUTH_TWO_FACTOR_ENFORCED');
$twoFactorEnforced = ! in_array(is_string($twoFactorSetting) ? strtolower(trim($twoFactorSetting)) : $twoFactorSetting, [false, '0', 'off', 'no'], true);

$taskWriter = ['tasks.create', 'tasks.update'];
$reportWriter = ['reports.create', 'reports.update'];

// MEAL's own work, kept from the General Manager (contracts §8). Targets are
// entered by MEAL only (D-23).
$mealOnly = ['meal-coverage.manage', 'report-templates.manage', 'evidence-types.manage', 'narrative-questions.manage'];
$targetWriter = ['targets.create', 'targets.update'];

// AI trial pilot (user decision 2026-11-05): drafting for MEAL, both Public
// Relations roles and Managers; the usage log for the General Manager.
$aiDrafter = ['ai.draft'];

// Rules that used to name a role inside a policy (roadmap 4.3), each held by
// the one role the policy named: any MEAL officer covers a project that has
// no responsible officer; the Public Relations Manager decides reopen
// requests while there is no MEAL manager; and the General Manager approves
// the strategic plan of a program that has no manager (through "everything").
$mealStandIn = ['meal-coverage.stand-in'];
$reopenStandIn = ['reports.reopen-stand-in'];

return [

    'groups' => $groups,

    /*
    |--------------------------------------------------------------------------
    | HR permissions
    |--------------------------------------------------------------------------
    |
    | The names HR brought into the catalog (roadmap 4.1). No built-in role
    | baseline lists them; the HR roles hold them.
    */
    'hr_permissions' => $hrPermissions,

    /*
    |--------------------------------------------------------------------------
    | Role aliases
    |--------------------------------------------------------------------------
    |
    | HR's role names beside the hub roles that mean the same thing to HR's
    | checks (roadmap 4.2). App\Support\Access\RoleAliases keeps them as rows
    | in role_aliases: a role created under one of these names is linked to
    | its alias, and `permissions:sync` links the ones still missing. Nothing
    | here grants a permission.
    */
    'role_aliases' => [
        'super-admin' => ['super-admin', 'Super Admin'],
        'general-manager' => ['general-manager', 'General Manager'],
        'manager' => ['manager', 'Managers'],
        'employee' => ['employee', 'Team Member'],
    ],

    /*
    |--------------------------------------------------------------------------
    | MEAL manager only
    |--------------------------------------------------------------------------
    |
    | Building the logframe and its targets belongs to the one person holding
    | the MEAL Manager slot (MEAL manager decision, 2026-11-13), not to every
    | MEAL officer: a role that grants one of these abilities is honoured only
    | for that person, or for a role listed under bypass_roles (see
    | Modules\Core\Models\User::checkPermissionTo()). Logging an indicator reading is
    | `indicators.record` and is not restricted.
    */
    'meal_manager_only' => [
        'abilities' => [
            'results.create', 'results.update', 'results.delete',
            'indicators.create', 'indicators.update', 'indicators.delete',
            'targets.create', 'targets.update', 'targets.delete',
            'indicator-library.create', 'indicator-library.update', 'indicator-library.delete',
        ],
        'bypass_roles' => ['Super Admin', 'General Manager'],
    ],

    /*
    |--------------------------------------------------------------------------
    | MEAL manager grants
    |--------------------------------------------------------------------------
    |
    | Granted to the MEAL Manager slot holder whatever their roles say (user
    | decision 2026-11-16): every proposal tab, editing, status and reviewers,
    | and the Funding tab's agreements and transfers. Recording the donor's
    | approval (D-70) and deleting stay out. MEAL officers get none of these.
    | See Modules\Core\Models\User::checkPermissionTo().
    */
    'meal_manager_grants' => [
        'proposals.view', 'proposals.update', 'proposals.manage-reviewers', 'proposals.correct-status',
        'donors.view',
        'funding-agreements.view', 'funding-agreements.create', 'funding-agreements.update',
        'transfers.view', 'transfers.create', 'transfers.update',
    ],

    /*
    |--------------------------------------------------------------------------
    | Administrator roles
    |--------------------------------------------------------------------------
    |
    | Always hold every permission (see App\Models\Role::booted()), and are the
    | only ones who may grant or remove roles carrying user-management rights
    | (see App\Policies\UserPolicy).
    */
    'administrator_roles' => ['Super Admin'],

    /*
    |--------------------------------------------------------------------------
    | Observer roles
    |--------------------------------------------------------------------------
    |
    | Roles that read the work but stay out of its review chain, whatever
    | their permissions say: they never validate a monthly report
    | (MonthlyReportPolicy) and read annual-plan updates as a summary only
    | (PlanUpdatePolicy). An administrator role is never an observer.
    */
    'observer_roles' => ['General Manager'],

    /*
    |--------------------------------------------------------------------------
    | Roles that count in recruitment
    |--------------------------------------------------------------------------
    |
    | 'all': every role a person holds counts, as in the rest of the hub
    | (roadmap 4.5, approved by the owner on 2026-11-20). 'active' brings
    | back the old HR rule: only the role a person is acting under. See
    | Modules\Hr\Support\Recruitment\Access; `hub:recruitment-access` lists
    | who differs between the two.
    */
    'recruitment_roles' => env('RECRUITMENT_ROLES', 'all'),

    /*
    |--------------------------------------------------------------------------
    | Scoped access grants
    |--------------------------------------------------------------------------
    |
    | A role or a permission given to one user on one scope for a time
    | (access_grants, read by App\Support\Access\AccessScope; roadmap 4.4).
    | Off by default: switched off, a grant gives nothing and only the
    | general permissions decide, exactly as before the table existed. The
    | project policy is the first reader; a grant on a program or a city
    | covers the projects under it.
    */
    'access_grants' => [
        'enabled' => (bool) env('ACCESS_GRANTS_ENABLED', false),
        'scope_types' => ['project', 'program', 'city', 'center', 'department'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default role
    |--------------------------------------------------------------------------
    |
    | The role an account is given by the Admin > Users form and
    | `user:create` when no role is chosen.
    */
    'default_role' => 'Team Member',

    /*
    |--------------------------------------------------------------------------
    | Required two-factor authentication
    |--------------------------------------------------------------------------
    |
    | Anyone holding one of these roles is kept on the security settings page
    | until they turn on two-factor authentication, and may not switch it off
    | (App\Http\Middleware\EnsureTwoFactorIsEnabled). AUTH_TWO_FACTOR_ENFORCED
    | switches the requirement off, as phpunit.xml does for the test suite.
    |
    | Two-factor is optional for every role (user decision 2026-09-28): each
    | person turns on email codes, the authenticator app or passkeys from the
    | security page. List role names here to make it required for them again.
    */
    'two_factor' => [
        'enforced' => $twoFactorEnforced,
        'required_roles' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Task department scoping
    |--------------------------------------------------------------------------
    |
    | task_department_scoped_roles only ever see tasks of their own HR
    | department, plus their own tasks and tasks on projects they answer for
    | (Modules\Tracking\Models\Scopes\TaskVisibilityScope). A bypass role sees every
    | department and wins when a user holds both kinds.
    |
    | task_status_and_files_only_roles (Media Team, UAT 2026-11-09) edit only
    | the tasks they created. On a colleague's own or department task they
    | may move the status and add or remove files, nothing else.
    */
    'task_department_scope_bypass_roles' => ['Super Admin', 'General Manager', 'Managers'],
    'task_status_and_files_only_roles' => ['Media Team'],
    'task_department_scoped_roles' => ['MEAL', 'Financial', 'Media Team', 'Public Relations', 'Public Relations Manager', 'Team Member'],

    /*
    |--------------------------------------------------------------------------
    | Department manager (W-14, D-65)
    |--------------------------------------------------------------------------
    |
    | Reading a whole department's tasks — the desk Tasks panel's `department`
    | scope and the department-tasks page — belongs to department managers,
    | which D-65 defines as an HR job level rather than a role or a permission.
    | App\Support\EmployeeProfile::isDepartmentManager() is the one definition;
    | it matches this name against job_levels.name_en or name_ar on a
    | normalised string, since HR may store the dash differently. Unrelated to
    | the task_department_* keys above, which scope what a user sees at all.
    */
    'department_manager_job_level' => 'Department Manager – Grade 5',

    /*
    |--------------------------------------------------------------------------
    | Role baselines
    |--------------------------------------------------------------------------
    |
    | Granted when a role is created (App\Models\Role::booted()). A permission
    | added to a list later reaches that role through `permissions:sync`,
    | which never takes one away. Every role views every module except the
    | activity log and the money pages; each list adds the money views that
    | role needs and what it may change. "Super Admin" is handled in code.
    | These nine names are the built-in roles: they cannot be renamed or
    | deleted (App\Policies\UserPolicy), but their permissions stay editable
    | from the Admin > Users roles tab.
    */
    'role_baselines' => [
        'Team Member' => $viewEverything,
        'Media Team' => [...$viewEverything, ...$taskWriter, ...$reportWriter],
        // The fundraising officer works the proposals (user story §1). The donor list
        // is read-only for them: only the PR Manager records a donor, and the
        // General Manager approves it.
        'Public Relations' => [
            ...$viewEverything, ...$fundraisingViews, ...$taskWriter, ...$reportWriter,
            'proposals.create', 'proposals.update',
            ...$aiDrafter,
        ],
        'Public Relations Manager' => [
            ...$viewEverything, ...$fundraisingViews, ...$taskWriter, ...$reportWriter,
            'donors.create', 'donors.update',
            'proposals.create', 'proposals.update', 'proposals.manage-reviewers', 'proposals.correct-status',
            // D-70: recording the donor's approval belongs to this role only.
            'proposals.approve',
            'projects.create',
            'funding-agreements.view', 'funding-agreements.create', 'funding-agreements.update',
            'reports.reopen', 'reports.aggregate', ...$reopenStandIn,
            ...$aiDrafter,
        ],
        'MEAL' => [
            ...$viewEverything, ...$taskWriter, ...$reportWriter, 'reports.approve', 'reports.mark-seen',
            'indicators.create', 'indicators.update', 'indicators.delete', 'indicators.record',
            'results.create', 'results.update', 'results.delete',
            'targets.create', 'targets.update', 'targets.delete',
            'indicator-library.create', 'indicator-library.update', 'indicator-library.delete',
            'reports.reopen', 'reports.aggregate',
            ...$mealOnly, ...$mealStandIn, 'sectors.manage', 'risk-library.manage',
            ...$aiDrafter,
        ],
        'Financial' => [
            ...$viewEverything, ...$financeViews, ...$fundraisingViews, ...$taskWriter,
            'donors.create', 'donors.update', 'donors.delete',
            'proposals.create', 'proposals.update', 'proposals.delete', 'proposals.manage-reviewers',
            'funding-agreements.create', 'funding-agreements.update', 'funding-agreements.delete',
            'transfers.create', 'transfers.update', 'transfers.delete',
            'accounts.create', 'accounts.update', 'accounts.delete',
        ],
        'Managers' => [
            ...$viewEverything, ...$taskWriter, 'tasks.delete', ...$reportWriter,
            'strategies.create', 'strategies.update', 'strategies.delete',
            // A proposal location may open a new program or center (A2, 2026-11-11).
            'programs.create', 'programs.update', 'centers.create',
            'annual-plans.create', 'annual-plans.update', 'annual-plans.delete',
            'projects.update', 'projects.delete',
            'risks.create', 'risks.update', 'risks.delete',
            // Building the logframe is the MEAL manager's alone (2026-11-13); managers still log readings.
            'indicators.record',
            // Their direct reports' timesheets (Modules\Hr\Support\Attendance\Roster).
            'attendance.view',
            ...$aiDrafter,
        ],
        'General Manager' => array_values(array_diff($allPermissions, [
            'access-management.manage-roles',
            'access-management.manage-permissions',
            'reports.mark-seen',
            // The MEAL manager reviews annual plans; the CEO only reads them.
            'annual-plans.review',
            'notification-settings.manage',
            'reports.reopen',
            'proposals.approve',
            ...$mealOnly,
            ...$mealStandIn,
            ...$reopenStandIn,
            ...$targetWriter,
            ...$aiDrafter,
            ...$hrOnly,
            ...$hrPermissions,
        ])),
    ],
];
