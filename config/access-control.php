<?php

/*
 * The close guard of an editable dialog (GuardsModalClose, A5-M). requestClose()
 * only compares the entries with what the dialog opened with and either hides
 * it or raises the "Discard your changes?" question; discardChanges() hides it
 * and clears that dialog's own form. Neither reads a record or writes one, and
 * the dialog name they take is checked against the component's own list.
 */
$closeGuard = ['requestClose', 'discardChanges'];

return [
    'administrator_roles' => ['super-admin', 'HR-SYR'],

    /*
     * Regional HR roles that cover more than their own city, by city slug.
     * A regional role not listed here covers exactly the city whose slug
     * follows "HR-" in its name.
     */
    'hr_role_cities' => [
        'HR-HMS' => ['HMS', 'RHO'],
    ],

    'system_roles' => [
        'super-admin',
        'general-manager',
        'HR-SYR',
        'HR-ALP',
        'HR-HMS',
        'HR-DMS',
        'HR-LAT',
        'HR-TAR',
        'manager',
        'employee',
    ],

    /*
     * Reference pages a role may not open, though its baseline still holds
     * the read permission (C0-Q4 answer 2, G-B-13 answer 2). The same list
     * takes them out of the menu and refuses their URL with 403, so the two
     * cannot drift. The permission stays: hr.positions.view also opens the org
     * chart and the colleague drawer, which every role keeps.
     * Read through User::canOpenReferencePage().
     */
    'reference_pages_hidden_from' => [
        'centers' => ['employee'],
        'programs' => ['employee'],
        'positions' => ['employee', 'manager'],
        'project-basics' => ['employee', 'manager'],
    ],

    'role_baselines' => [
        'general-manager' => [
            'hr.statistics.view', 'hr.employees.view', 'hr.employees.create', 'hr.employees.update', 'hr.employees.export',
            'hr.contracts.view', 'hr.contracts.create', 'hr.contracts.update', 'hr.contracts.export',
            'hr.programs.view', 'hr.programs.create', 'hr.programs.update', 'hr.centers.view',
            'hr.positions.view', 'hr.leaves.view-own', 'hr.leaves.view-all', 'hr.leaves.create',
            'hr.leaves.update', 'hr.leaves.decide', 'hr.news.view', 'hr.news.create', 'hr.news.update',
            'hr.checklists.view-own',
            'hr.checklists.manage', 'hr.project-basics.view', 'hr.archives.view',
            'hr.archives.create', 'hr.archives.update', 'hr.archives.delete', 'hr.archives.share',
            'hr.archives.download',
            // Read-only, across every city like HR-SYR: see Badge::scopeVisibleTo().
            'hr.badges.view',
        ],
        'manager' => [
            // Read-only, and only their direct reports' contracts (G-C-8):
            // see Contract::scopeVisibleTo().
            'hr.contracts.view',
            'hr.programs.view', 'hr.centers.view', 'hr.positions.view', 'hr.leaves.view-own',
            'hr.leaves.view-all', 'hr.leaves.create', 'hr.leaves.update', 'hr.leaves.decide',
            'hr.news.view', 'hr.news.create', 'hr.news.update',
            'hr.checklists.view-own', 'hr.project-basics.view', 'hr.archives.view',
            'hr.archives.create', 'hr.archives.update', 'hr.archives.delete', 'hr.archives.share',
            'hr.archives.download',
        ],
        'employee' => [
            'hr.programs.view', 'hr.centers.view', 'hr.positions.view', 'hr.leaves.view-own',
            'hr.leaves.create', 'hr.leaves.update', 'hr.archives.view',
            'hr.archives.create', 'hr.archives.update', 'hr.archives.delete', 'hr.archives.share',
            'hr.archives.download', 'hr.checklists.view-own', 'hr.project-basics.view',
        ],
    ],

    'groups' => [
        'hub.access-management' => ['view', 'manage-employees', 'manage-roles', 'manage-permissions'],
        'hr.statistics' => ['view'],
        'hr.employees' => ['view', 'create', 'update', 'delete', 'export'],
        'hr.contracts' => ['view', 'create', 'update', 'delete', 'export'],
        'hr.programs' => ['view', 'create', 'update', 'delete'],
        'hr.centers' => ['view', 'create', 'update', 'delete', 'export'],
        'hr.positions' => ['view', 'create', 'update', 'delete', 'export'],
        'hr.leaves' => ['view-own', 'view-all', 'create', 'update', 'delete', 'decide', 'review', 'manage-balances', 'export'],
        'hr.project-basics' => ['view', 'create', 'update', 'delete', 'export'],
        'hr.news' => ['view', 'create', 'update', 'delete'],
        'hr.badges' => ['view', 'create', 'update', 'delete'],
        'hr.archives' => ['view', 'create', 'update', 'delete', 'share', 'download'],
        'hr.checklists' => ['view-own', 'view-all', 'manage', 'deliver', 'receive'],
        'hub.activity-log' => ['view'],
    ],

    /*
     * Server-side guards for mutation methods with an unambiguous action.
     * Components that use one save method for both create and update authorize
     * inside the component because the required permission depends on state.
     */
    'livewire' => [
        'access-management.index' => [
            'saveEmployeeAccess' => 'hub.access-management.manage-employees',
            'saveRolePermissions' => 'hub.access-management.manage-permissions',
            'selectAllRolePermissions' => 'hub.access-management.manage-permissions',
            'clearRolePermissions' => 'hub.access-management.manage-permissions',
            'createRole' => 'hub.access-management.manage-roles',
            'renameRole' => 'hub.access-management.manage-roles',
            'deleteRole' => 'hub.access-management.manage-roles',
        ],
        // edit() discloses a city into the modal, like the other basics modules.
        'basics.city-module' => ['edit' => 'hr.project-basics.update'],
        'basics.city-table' => [
            'toggleItemStatusConfirmed' => 'hr.project-basics.update',
            'exportExcel' => 'hr.project-basics.export',
        ],
        'basics.contract-type-table' => ['delete' => 'hr.project-basics.delete', 'exportExcel' => 'hr.project-basics.export'],
        'basics.checklist-item-table' => ['delete' => 'hr.project-basics.delete', 'exportExcel' => 'hr.project-basics.export'],
        'basics.leave-type-table' => ['delete' => 'hr.project-basics.delete', 'exportExcel' => 'hr.project-basics.export'],
        'basics.job-level-table' => ['delete' => 'hr.project-basics.delete', 'exportExcel' => 'hr.project-basics.export'],
        'basics.department-table' => ['delete' => 'hr.project-basics.delete', 'exportExcel' => 'hr.project-basics.export'],
        'basics.course-category-table' => ['delete' => 'hr.project-basics.delete', 'exportExcel' => 'hr.project-basics.export'],
        'basics.annual-leave-rule-table' => ['delete' => 'hr.project-basics.delete', 'exportExcel' => 'hr.project-basics.export'],
        'basics.termination-reason-table' => ['delete' => 'hr.project-basics.delete'],
        'program-management.program.create' => ['save' => 'hr.programs.create'],
        'program-management.program.update' => ['save' => 'hr.programs.update'],
        'program-management.program.index' => [
            'delete' => 'hr.programs.delete',
            'toggleItemStatusConfirmed' => 'hr.programs.update',
            'show' => 'hr.programs.view',
        ],
        'program-management.program.detail' => [
            'delete' => 'hr.programs.delete',
            'toggleItemStatusConfirmed' => 'hr.programs.update',
        ],
        'program-management.center.center-create' => ['save' => 'hr.centers.create'],
        'program-management.center.centers-table' => [
            'delete' => 'hr.centers.delete',
            'toggleItemStatusConfirmed' => 'hr.centers.update',
            'exportExcel' => 'hr.centers.export',
        ],
        'position.position-create' => ['save' => 'hr.positions.create'],
        'position.position-edit' => ['save' => 'hr.positions.update'],
        'position.position-table' => [
            'delete' => 'hr.positions.delete',
            'toggleItemStatusConfirmed' => 'hr.positions.update',
            'exportExcel' => 'hr.positions.export',
        ],
        'employee.employee-create' => [
            'saveInformation' => 'hr.employees.create',
            'saveDraftRecord' => 'hr.employees.create',
            'createDraftAndContinue' => 'hr.employees.create',
            'positionOptionsForDraft' => 'hr.employees.create',
            'programOptionsForDraft' => 'hr.employees.create',
            'citiesForDraft' => 'hr.employees.create',
            'canPickAnyDraftCity' => 'hr.employees.create',
        ],
        'employee.employee-edit' => [
            'saveChecklist' => 'hr.employees.update',
            'saveInformation' => 'hr.employees.update',
            'saveDraftRecord' => 'hr.employees.update',
            'confirmDiscardDraftRecord' => 'hr.employees.update',
            'discardDraftRecord' => 'hr.employees.update',
            'saveEducation' => 'hr.employees.update',
            'saveCourses' => 'hr.employees.update',
            'saveExperience' => 'hr.employees.update',
            'saveRelatives' => 'hr.employees.update',
            'saveBadges' => 'hr.badges.create',
            'saveAttachments' => 'hr.employees.update',
            'removeAttachment' => 'hr.employees.update',
            'removeEducation' => 'hr.employees.update',
            'removeCourse' => 'hr.employees.update',
            'removeExperience' => 'hr.employees.update',
            'removeRelative' => 'hr.employees.update',
            'addBadge' => 'hr.badges.create',
            'removeBadge' => 'hr.badges.create',
            'initiateReturn' => 'hr.employees.update',
            'finalizeReturnCycle' => 'hr.employees.update',
        ],
        // B4: opens the read-only drawer; the method re-runs EmployeePolicy::view and
        // ::viewDirectory on the record inside the active city scope.
        'employee.employee-drawer' => ['open' => 'hr.employees.view'],
        'employee.employee-table' => [
            'delete' => 'hr.employees.delete',
            'toggleItemStatusConfirmed' => 'hr.employees.update',
            'exportExcel' => 'hr.employees.export',
        ],
        // The composer exposes one option load, one entitlement preview, and
        // the save itself on both screens; create additionally exposes the
        // draft-contract lookup that only makes sense when creating a new
        // contract (see ContractCreate::draftContractFor()).
        'contract.contract-create' => [
            'submit' => 'hr.contracts.create',
            'reconcileOperation' => 'hr.contracts.create', // X1-I: lookup by token, never writes
            'locationOptions' => 'hr.contracts.create',
            'employeeSearchOptions' => 'hr.contracts.create',
            'leavePreview' => 'hr.contracts.create',
            'draftContractFor' => 'hr.contracts.create',
        ],
        'contract.contract-edit' => [
            'submit' => 'hr.contracts.update',
            'locationOptions' => 'hr.contracts.update',
            'employeeSearchOptions' => 'hr.contracts.update',
            'leavePreview' => 'hr.contracts.update',
        ],
        // A renewal creates a contract. The permission here is the coarse
        // gate; ContractRenewModal re-reads the contract and runs the `renew`
        // policy on every one of these, which is what enforces city scope and
        // rejects a terminated source.
        'contract.contract-renew-modal' => [
            'open' => 'hr.contracts.create',
            'renewAsIs' => 'hr.contracts.create',
            'reconcileOperation' => 'hr.contracts.create', // X1-I: lookup by token, never writes
            'renewWithEdit' => 'hr.contracts.create',
        ],
        // Both auto-renew actions also run ContractPolicy::manageAutoRenew on
        // the specific contract (HR role, hr.contracts.update, visible to the
        // caller), which is the city/record control; the permission here is
        // the coarse gate. The confirmed write normally arrives as a dispatched
        // event, so the in-method Gate is what holds on that path.
        // Opens the read-only drawer; the method re-runs ContractPolicy::view on the record.
        'contract.contract-drawer' => ['open' => 'hr.contracts.view'],
        'contract.contracts-table' => [
            'delete' => 'hr.contracts.delete',
            'exportExcel' => 'hr.contracts.export',
            'confirmToggleAutoRenew' => 'hr.contracts.update',
            'toggleAutoRenewConfirmed' => 'hr.contracts.update',
        ],
        'news.news-table' => ['delete' => 'hr.news.delete'],
        'employee.leave.all-leave-request-module' => [
            'create' => 'hr.leaves.create', 'delete' => 'hr.leaves.delete',
            'openDecision' => 'hr.leaves.decide', 'submitDecision' => 'hr.leaves.decide',
            'reconcileOperation' => 'hr.leaves.decide', // X1-I: reads the request's state, decides nothing
        ],
        'employee.leave.all-leave-request-table' => [
            'delete' => 'hr.leaves.delete', 'markAsReviewed' => 'hr.leaves.review',
        ],
        'employee.leave.my-leave-request-table' => ['delete' => 'hr.leaves.delete'],
        // 'edit' loads a balance record into the modal, before processSave().
        'employee.leave.emp-leave-balance-module' => [
            'processSave' => 'hr.leaves.manage-balances',
            'edit' => 'hr.leaves.manage-balances',
        ],
        'employee.leave.emp-leave-balance-table' => [
            'exportExcel' => 'hr.leaves.export',
            'edit' => 'hr.leaves.manage-balances',
        ],
        'badges.badges-table' => ['delete' => 'hr.badges.delete'],
        'program-management.default-deliverable.default-deliverables-table' => [
            'delete' => 'hr.checklists.manage', 'exportExcel' => 'hr.checklists.manage',
        ],
        'archives.archived-files-module' => [
            'createFolder' => 'hr.archives.create',
            'editPermissions' => 'hr.archives.share',
            'savePermissions' => 'hr.archives.share',
            'updatedFilesToUpload' => 'hr.archives.create',
            'downloadFile' => 'hr.archives.download',
            'previewFile' => 'hr.archives.view',
            'confirmFileDeletion' => 'hr.archives.delete',
            'confirmFolderDeletion' => 'hr.archives.delete',
            'deleteItem' => 'hr.archives.delete',
            'startEditingFolder' => 'hr.archives.update',
            'saveEditedFolder' => 'hr.archives.update',
            'startEditingFile' => 'hr.archives.update',
            'saveEditedFile' => 'hr.archives.update',
            'toggleUser' => 'hr.archives.share',
            'toggleSelectAllVisible' => 'hr.archives.share',
        ],
        'checklist-items.pending-items' => ['markDelivered' => 'hr.checklists.deliver'],
        'checklist-items.pending-returns' => ['markReceived' => 'hr.checklists.receive'],

        // Project-basics lookup tables. create/edit open the modal and disclose
        // the record; save() is create-or-update and decides its own permission
        // in-method, so it appears under livewire_exempt instead.
        'basics.annual-leave-rule-module' => ['create' => 'hr.project-basics.create', 'edit' => 'hr.project-basics.update'],
        'basics.checklist-item-module' => ['create' => 'hr.project-basics.create', 'edit' => 'hr.project-basics.update'],
        'basics.contract-type-module' => [
            'create' => 'hr.project-basics.create',
            'edit' => 'hr.project-basics.update',
            'delete' => 'hr.project-basics.delete',
        ],
        'basics.course-category-module' => ['create' => 'hr.project-basics.create', 'edit' => 'hr.project-basics.update'],
        'basics.department-module' => ['create' => 'hr.project-basics.create', 'edit' => 'hr.project-basics.update'],
        'basics.job-level-module' => ['create' => 'hr.project-basics.create', 'edit' => 'hr.project-basics.update'],
        'basics.leave-type-module' => ['create' => 'hr.project-basics.create', 'edit' => 'hr.project-basics.update'],
        'basics.termination-reason-module' => ['create' => 'hr.project-basics.create', 'edit' => 'hr.project-basics.update'],

        'program-management.center.centers-module' => ['create' => 'hr.centers.create', 'edit' => 'hr.centers.update'],
        'program-management.default-deliverable.default-deliverables-module' => [
            'create' => 'hr.checklists.manage',
            'edit' => 'hr.checklists.manage',
        ],
        // Streams the whole org directory to CSV; had no check of its own.
        'position.directory' => ['exportCsv' => 'hr.positions.export'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reviewed Livewire actions that need no middleware permission
    |--------------------------------------------------------------------------
    |
    | Every public method on a Livewire component is callable over HTTP with
    | arbitrary arguments, whether or not the page renders a button for it.
    | ActiveLivewirePermissionMiddleware only checks methods listed in
    | 'livewire' above and lets everything else through, so an unlisted action
    | is unprotected *and* silent about it.
    |
    | LivewireMutationCoverageTest turns that silence into a build failure:
    | every public action written in app/Livewire must appear either in
    | 'livewire' above or here. This list is therefore a record of decisions,
    | not a bypass — each group below says why the middleware layer adds
    | nothing, and in most cases the real control (a Gate call, a scoped query,
    | or a route middleware) is stronger than a permission name would be.
    |
    | Adding a method here without a reason defeats the entire mechanism.
    |
    */

    'livewire_exempt' => [

        // --- Authentication -------------------------------------------------
        // Reached before a session exists, or acting only on the caller's own
        // account. A permission check would be meaningless: there is no active
        // role yet. Login is rate limited per account and per IP.
        'auth.login' => ['login', 'updatedEmail'],
        'auth.register' => ['register'],
        'auth.forgot-password' => ['sendPasswordResetLink'],
        'auth.reset-password' => ['resetPassword'],
        'auth.confirm-password' => ['confirmPassword'],
        'auth.verify-email' => ['sendVerification', 'logout'],
        // switchTo() calls User::setActiveRole(), which aborts unless the role
        // is already assigned to this user; it grants nothing new.
        'auth.role-switcher' => ['switchTo', 'refreshRoles'],
        'settings.password' => ['updatePassword'],

        // --- Confirmation dialogs -------------------------------------------
        // These only render a "are you sure?" prompt. The action it confirms
        // is the mapped one (delete / toggleItemStatusConfirmed), and that is
        // where the permission and the policy check live.
        'badges.badges-table' => ['confirmDelete'],
        'basics.annual-leave-rule-table' => ['confirmDelete'],
        'basics.checklist-item-table' => ['confirmDelete'],
        'basics.city-table' => ['confirmToggleItemStatus'],
        'basics.contract-type-table' => ['confirmDelete'],
        'basics.course-category-table' => ['confirmDelete'],
        'basics.department-table' => ['confirmDelete'],
        'basics.job-level-table' => ['confirmDelete'],
        'basics.leave-type-table' => ['confirmDelete'],
        'basics.termination-reason-table' => ['confirmDelete'],
        // Holidays are set by national HR and by program managers, who hold no
        // project-basics permission: every action checks HolidayProjects
        // (who may change which holiday) in the component itself.
        'basics.holiday-table' => ['confirmDelete', 'delete'],
        // updatedPaginators (not a dialog) stores the caller's own page
        // position in their own session (the key includes auth()->id()); the
        // rows still come from query(), which applies Contract::scopeVisibleTo.
        // B3 list states: view state / text only, no writes.
        // B4: setPreset / clearAll / removeChip / dismissStateNotice change only the caller's
        // own view state (setPreset also 403s a preset the active role may not use); every
        // row still comes from query() -> Contract::scopeVisibleTo. The rest is display text.
        // B4-D saved views: saveView / applyView / renameView / deleteView / savedViews touch only
        // the caller's OWN saved_table_views rows (every query is scoped by auth id, this table and
        // the active role context; another user's id is a 404). They store table state only (search,
        // filters, sort, per-page, columns), never rows or pay, and applyView re-runs the table's
        // sanitizing, so rows still come from query().
        'contract.contracts-table' => [
            'saveView', 'applyView', 'renameView', 'deleteView', 'savedViews',
            'confirmDelete', 'updatedPaginators', 'updatedPerPage', 'updatedRemembersTableState', 'clearAllFilters',
            'renderEmptyState', 'retryLoad', 'stateAnnouncement', 'setPreset', 'clearAll', 'removeChip',
            'dismissStateNotice', 'canClearAll', 'activeFilterChips', 'cardView',
        ],
        // Closing the read-only drawer only clears its own locked id.
        'contract.contract-drawer' => ['close'],
        'news.news-table' => ['confirmDelete'],
        'position.position-table' => ['confirmDelete', 'confirmToggleItemStatus'],
        'program-management.center.centers-table' => ['confirmDelete', 'confirmToggleItemStatus'],
        'program-management.default-deliverable.default-deliverables-table' => ['confirmDelete'],
        'program-management.program.detail' => ['confirmDelete', 'confirmToggleItemStatus'],
        'program-management.program.index' => ['confirmDelete', 'confirmToggleItemStatus', 'loadMore'],
        'employee.employee-table' => [
            'confirmDelete', 'confirmToggleItemStatus',
            'saveView', 'applyView', 'renameView', 'deleteView', 'savedViews', // B4-D saved views: own rows only, see contract.contracts-table
            // X2-I lifecycle hooks: Livewire refuses direct calls to updated*.
            // They only start an anonymous lookup attempt (TaskTelemetry).
            'updatedGlobalSearch', 'updatedSearch',
            'clearAllFilters', 'renderEmptyState', 'retryLoad', 'stateAnnouncement', // B3: view state / text only
            // B4: setView / clearAll / removeChip / dismissStateNotice change only the caller's own
            // view state (setView falls back to Active for a view the role may not use); rows still
            // come from query(). The rest is display text or an Alpine expression.
            'setView', 'clearAll', 'removeChip', 'dismissStateNotice', 'canClearAll', 'activeFilterChips',
            'cardView', 'rowClickAction', 'canUseInactiveView', 'canUseIncompleteView', 'effectiveView',
            'updatedPaginators', 'updatedPerPage', 'updatedRemembersTableState',
        ],
        // Closing the read-only drawer only clears its own id.
        'employee.employee-drawer' => ['close'],

        // --- X2-I task telemetry --------------------------------------------
        // dehydrateTracksTaskAttempt is a Livewire lifecycle hook: dehydrate*
        // cannot be called from the browser. It only flags an anonymous
        // attempt as having met a validation error (TracksTaskAttempt).
        'contract.contract-create' => ['dehydrateTracksTaskAttempt', 'mountRemembersReturnTo', // C2 lifecycle hook (Livewire refuses direct calls to mount*): reads ?from through SafeReturn, writes nothing
        ],

        // --- Authorized inside the method -----------------------------------
        // save() is create-or-update, so a single permission name in the
        // middleware map would be wrong in one direction or the other. Each of
        // these calls authorizeActivePermission() with the correct permission
        // for the branch it is taking, which the middleware cannot do.
        'basics.annual-leave-rule-module' => ['save', ...$closeGuard],
        'basics.checklist-item-module' => ['save', ...$closeGuard],
        'basics.contract-type-module' => ['save', ...$closeGuard],
        'basics.course-category-module' => ['save', ...$closeGuard],
        'basics.department-module' => ['save', ...$closeGuard],
        'basics.job-level-module' => ['save', ...$closeGuard],
        'basics.leave-type-module' => ['save', ...$closeGuard],
        'basics.termination-reason-module' => ['save', ...$closeGuard],
        'basics.holiday-module' => ['create', 'createOn', 'edit', 'save', 'selectGovernorate', ...$closeGuard],
        // choose() records the caller's own request for their own birthday;
        // decide() is refused unless the caller holds the volunteer's
        // direct-manager position (BirthdayHoliday::canDecide).
        'employee.leave.birthday-holiday-card' => ['choose', 'decide'],
        // save() writes only cities.hr_hotline (the ID card's emergency number,
        // A2D-Q7) after its own hr.project-basics.update check.
        'basics.city-module' => ['save', ...$closeGuard],
        // Each re-reads the employee by its locked id and runs the
        // digital-id.manage or digital-id.report-lost gate (DigitalIdPolicy)
        // against the request's active role. A permission name would be wrong:
        // reportLost belongs to the card's owner, who holds no HR permission,
        // and manage is city-scoped HR, which a permission cannot express.
        'employee-cards.manage-digital-id' => ['reissue', 'disable', 'enable', 'reportLost'],
        // updatedFormNewsableType() only clears dependent form selections;
        // it does not read a record or persist data. save() authorizes before writing.
        'news.news-create' => ['save', 'updatedFormNewsableType',
            'mountRemembersReturnTo', // C2 lifecycle hook (Livewire refuses direct calls to mount*): reads ?from through SafeReturn, writes nothing
        ],
        // Also runs assertCityIsAuthorized() and a Gate check on the city.
        'program-management.center.centers-module' => ['save', ...$closeGuard],
        'program-management.default-deliverable.default-deliverables-module' => ['save', ...$closeGuard],
        // Each calls authorizeActivePermission() plus a LeaveRequestPolicy
        // check on the specific record, which is the stronger control.
        'employee.leave.my-leave-request-module' => [
            'create', 'edit', 'processSave', 'confirmSave', 'openDetails',
            'reconcileOperation', // X1-I: lookup by the form's token; authorizes hr.leaves.create in the method
            'closeDetails', 'selectedLeave', 'submittedLeave', 'refreshCounts', 'setActiveTab',
            // B3: list states. resetStatusFilter only sets the tab to "all" (a filter on the
            // caller's own requests); retryLoad re-renders and writes nothing.
            'resetStatusFilter', 'retryLoad',
            // Only resets form.type to "day" when an own non-annual balance is picked.
            'updatedFormLeaveBalanceId',
            // Read-only, self-scoped: prices the typed window against a balance
            // of the signed-in employee's own active contract; writes nothing.
            'leaveImpact',
            ...$closeGuard,
            'dehydrateTracksTaskAttempt', // X2-I lifecycle hook, see contract.contract-create
            'mountRemembersReturnTo', // C2 lifecycle hook (Livewire refuses direct calls to mount*): reads ?from through SafeReturn, writes nothing
        ],
        'employee.leave.all-leave-request-module' => [
            'edit', 'show', 'processSave', 'confirmSave', 'confirmDelete',
            'closeDecision', 'closeDetails', 'refreshCounts', 'setActiveTab',
            'resetStatusFilter', // B3: sets the tab to "all"; a view filter only
            'updatedFormEmployeeId',
            ...$closeGuard,
            'dehydrateTracksTaskAttempt', // X2-I lifecycle hook, see contract.contract-create
            'mountRemembersReturnTo', // C2 lifecycle hook (Livewire refuses direct calls to mount*): reads ?from through SafeReturn, writes nothing
        ],
        'employee.leave.emp-leave-balance-module' => ['confirmSave', ...$closeGuard],
        // B3 HasScreenListStates: clearAllFilters resets search/filters/tab (view state only),
        // renderEmptyState and stateAnnouncement render text, retryLoad re-renders. No writes.
        // G-B-4: cardView only names the phone card's Blade view; rows still come from query().
        'employee.leave.all-leave-request-table' => [
            'edit', 'confirmDelete', 'confirmHrReview', 'refreshTable', 'setStatusFilter',
            'clearAllFilters', 'renderEmptyState', 'retryLoad', 'stateAnnouncement', 'cardView',
        ],
        'employee.leave.my-leave-request-table' => ['edit', 'confirmDelete', 'setStatusFilter', 'clearAllFilters', 'renderEmptyState', 'retryLoad', 'stateAnnouncement', 'cardView'],
        'employee.leave.emp-leave-balance-table' => ['clearAllFilters', 'renderEmptyState', 'retryLoad', 'stateAnnouncement', 'cardView'],
        // B3: read-only lists of the caller's own checklist rows; retryLoad re-renders only.
        'checklist-items.all-items' => ['retryLoad'],
        'checklist-items.pending-items' => ['retryLoad'],
        'checklist-items.pending-returns' => ['retryLoad'],
        'checklist-items.done-requests-table' => ['clearAllFilters', 'renderEmptyState', 'retryLoad', 'stateAnnouncement'],
        // A record read, not a state toggle: it takes a caller-supplied
        // employee id. It authorizes EmployeePolicy::viewDirectory on that id,
        // and the drawer re-checks on every render, so a role switch or a
        // former employee drops back to a uniform not-found (access-policy.md
        // §4.4). The middleware could only add hr.positions.view, which every
        // role already holds and which says nothing about the record.
        'position.profile-drawer' => ['openProfile'],

        // --- Scoped to the caller -------------------------------------------
        // These resolve their subject from auth() and never accept a
        // caller-supplied owner id, so there is no cross-user surface for a
        // permission to protect.
        'dashboard.employee-notes-card' => [
            'addText', 'createNote', 'toggleDone', 'updateContent', 'delete', 'restoreNote', 'retryLoad',
        ],
        'notifications.dropdown' => [
            'markAsRead', 'markAllAsRead', 'openNotification', 'refreshList', 'safeNotificationUrl',
            'retryLoad', // B3: re-renders the read-only list; marks nothing read
        ],
        'notifications.index' => [
            'markAsRead', 'markAllAsRead', 'openNotification', 'refreshList', 'safeNotificationUrl',
            'retryLoad', // B3: re-renders the read-only list; marks nothing read
        ],
        'notifications.counter' => ['updateCount', 'syncCount'],
        // D3 / DEC-08 feedback form: every signed-in role may send feedback, so
        // there is no permission to check. open() only shows the dialog;
        // submit() aborts for a guest, validates, is rate limited per user, and
        // writes one row owned by the caller whose role, language and page are
        // derived on the server (InAppFeedbackRecorder). Reading is separate.
        'feedback.feedback-form' => ['open', 'submit', ...$closeGuard],
        // D3 reading list: boot() runs Gate feedback.read (User::canReadFeedback)
        // on every request; a permission name here would be wrong, since
        // hasActivePermission() says yes to HR-SYR. updatedCategory is a
        // lifecycle hook that only changes the filter.
        'feedback.feedback-index' => ['updatedCategory'],
        'layout.sidebar' => ['toggle', 'close', 'refreshCounts'],
        // D2 phone bar: re-renders its own links and the inbox count (WorkInbox, scoped to the caller).
        'layout.bottom-nav' => ['refreshInbox'],

        // --- Component-state only -------------------------------------------
        // Tab switches, wizard steps, modal open/close, filter changes, zoom
        // and pagination controls. They mutate Livewire properties, never the
        // database, and the page they belong to is already route-gated.
        'access-management.index' => [
            'showTab', 'selectRole', 'selectUser', 'showAssignedUsers', 'clearSearch',
            'employeeAccessIsDirty', 'updatedSearch', 'updatedRoleFilter',
            'updatedStatusFilter', 'updatedEmployeeRoleIds',
        ],
        'archives.archived-files-module' => [
            'loadContent', 'addFilter', 'removeFilter', 'clearFilters',
            'closePreviewModal', 'closePermissionsModal', 'updatedUserSearch',
            'updatedPermissionFilterType', 'updatedPermissionFilterValue',
        ],
        'badges.badges-module' => ['openBadgeModal'],
        // Neither contract.contract-create nor contract.contract-edit has any
        // exempt action: the wizard's step navigation and updated* hooks were
        // removed along with the wizard itself, and every action that remains
        // is mapped to a permission above.
        'contract.contract-details' => ['nextPage', 'prevPage', 'setPage'],
        // Dismisses the modal and clears its state. Writes nothing; the two
        // actions that do write are mapped to hr.contracts.create above.
        'contract.contract-renew-modal' => ['close', ...$closeGuard],
        'dashboard.calendar-card' => ['next', 'prev'],
        // B3: retryLoad re-renders read-only data; placeholder renders the lazy skeleton.
        'dashboard.contract-type-employees-statistics' => ['retryLoad'],
        'dashboard.department-employee-statistics' => ['retryLoad'],
        'dashboard.index' => ['retryLoad'],
        'dashboard.job-level-employee-statistics' => ['retryLoad'],
        'dashboard.salary-per-employee-statistics' => ['retryLoad'],
        'dashboard.upcoming-leaves-card' => ['retryLoad'],
        'dashboard.no-leaves-employees-statistics' => ['retryLoad', 'placeholder'],
        'dashboard.soon-ending-contracts-card' => ['retryLoad', 'placeholder'],
        // skipDraft is pure navigation (a redirect, no data read or write) and
        // updatedDraftCityId/updatedDraftProgramId only reset the draft
        // cascade's own in-memory selections — the same class as
        // setActiveTab, not the mapped createDraftAndContinue/
        // positionOptionsForDraft/programOptionsForDraft/citiesForDraft/
        // canPickAnyDraftCity above.
        'employee.employee-create' => ['setActiveTab', 'toggleBoardMember', 'skipDraft', 'updatedDraftCityId', 'updatedDraftProgramId',
            'mountRemembersReturnTo', // C2 lifecycle hook (Livewire refuses direct calls to mount*): reads ?from through SafeReturn, writes nothing
        ],
        // C3 work inbox: no permission of its own, by design (approval-source-contract.md §5).
        // Each action re-reads the record and runs its domain owner, which authorizes inside
        // its lock: decide -> LeaveRequestForm::submitDecision (Gate decide, LeaveDecisionAuthority),
        // markSeen/object -> LeaveRequestReview (Gate review/object), confirmItem/applyBatch ->
        // ChecklistWorkflow (row scoped to the actor, plus hr.checklists.deliver/receive),
        // finalizeItem -> Gate update on the employee + ChecklistWorkflow::finalizeReturn.
        // previewBatch writes nothing; setFilter changes the view only.
        // C5 team calendar: move/goToday/openDay only change the period shown.
        // What is shown comes from TeamCoverage on every render, scoped to the
        // active role; the URL values are re-checked there.
        'team.calendar' => ['move', 'goToday', 'openDay'],
        // approveProfile/rejectProfile (D1): ProfileChangeRequests::canDecide, i.e.
        // HR of the employee's city through EmployeePolicy::update, under a row lock.
        'inbox.index' => ['setFilter', 'decide', 'markSeen', 'object', 'confirmItem', 'finalizeItem', 'previewBatch', 'applyBatch', 'approveProfile', 'rejectProfile'],
        // C2 header search: a lifecycle hook (Livewire refuses direct calls to updated*) that only
        // trims the term and counts it against the per-user search limit. Results come from
        // DirectoryAccess::constrain() and the active role's menu on every render.
        'layout.global-search' => ['updatedQ'],
        // The add*/openInitiateReturnModal methods stage rows in the in-memory
        // form object; nothing is written until the mapped save* action runs,
        // and mount() has already authorized 'update' on this employee.
        'employee.employee-edit' => [
            'setActiveTab', 'toggleBoardMember', 'addEducation', 'addCourse',
            'addExperience', 'addRelative', 'addAttachment', 'openInitiateReturnModal',
        ],
        // toggleVacancies only flips whether empty positions are drawn and
        // rebuilds the same tree; vacant positions hold no person's data.
        'position.org-chart' => [
            'zoomIn', 'zoomOut', 'resetZoom', 'fitToScreen', 'expandAll',
            'collapseAll', 'toggleCollapse', 'toggleDensity', 'resetFilters',
            'toggleVacancies',
        ],
        'position.directory' => ['resetFilters'],
        // Reset the dependent center selection and pass the city through
        // validPositionFormCityId(), which refuses a city outside the
        // caller's scope. Nothing is written until the mapped save() runs.
        'position.position-create' => ['updatedFormCityId'],
        'position.position-edit' => ['updatedFormCityId'],
        'program-management.center.center-details' => ['switchTab'],
        'news.today-events-card' => ['loadEvents'],

        // --- Route-gated reads ----------------------------------------------
        // Reached only through routes carrying active_permission:hr.statistics.view,
        // and both now apply the caller's city scope to every aggregate.
        'statistics.contract-statistics' => ['loadData', 'resetFilters'],
        'employee.employee-statistics' => ['getStatistics'],
        // D1 profile section editing. Not a single permission: openSection and
        // saveSection check EmployeeProfile::canEditSection() on every call
        // (EmployeePolicy::update for HR in scope, or the active owner for
        // their own contact and skills, profile-contract.md Q5), write only
        // that section's columns, and refuse a stale save. reloadSection
        // re-opens through the same check; the close guard writes nothing.
        // openProposal/submitProposal: the active owner only, for their own record
        // (ProfileChangeRequests::canPropose); nothing reaches the profile until
        // HR of the employee's city approves it in the inbox (section 5.1).
        'employee.employee-profile' => ['openSection', 'saveSection', 'reloadSection', 'openProposal', 'submitProposal', ...$closeGuard],
    ],
];
