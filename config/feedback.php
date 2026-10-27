<?php

/*
 * In-app feedback (D3, DEC-08). Read by the development (IT) team only, text
 * only, deleted after one year. See docs/ux/d3-feedback-notes.md.
 */
return [
    // DEC-08: deleted after one year (InAppFeedback::prunable(), model:prune).
    'retention_days' => 365,

    'max_length' => 2000,

    'categories' => ['problem', 'idea', 'question'],

    // Submissions per user per window.
    'rate_limit' => [
        'max_attempts' => 5,
        'decay_seconds' => 3600,
    ],

    /*
     * Who may read feedback (User::canReadFeedback()). There is no IT role, so
     * DEC-08's "IT team" is: the active role is listed here (super-admin is the
     * developers' system account), or the user holds `permission` as a DIRECT
     * grant. A permission held through a role does not count: saving an
     * administrator role in access management hands it every permission in the
     * table, HR-SYR included, and User::hasActivePermission() answers yes for
     * every administrator role anyway. The permission is deliberately not in
     * access-control.groups, so no role is ever auto-granted it.
     */
    'reader_roles' => ['super-admin'],

    'permission' => 'feedback.view',
];
