<?php

/*
|--------------------------------------------------------------------------
| Product tours
|--------------------------------------------------------------------------
|
| One entry per page. `routes` is matched with request()->routeIs(), so
| wildcards such as 'leaves.*' work. Each step key doubles as its translation
| key: the copy for dashboard.welcome lives at tour.dashboard.welcome.title /
| .body in resources/lang/{en,ar}/tour.php.
|
| Step options:
|   target     value of a data-tour="..." attribute on the page; omit for a
|              centred step with no highlight (good for a welcome or wrap-up)
|   placement  preferred side: top | bottom | start | end (start/end follow
|              the reading direction, so they flip in Arabic). Falls back to
|              whichever side has room.
|
| A step whose target is missing or hidden when the tour starts is skipped,
| so steps may point at cards that only some roles or some data states render.
| The sidebar steps rely on this: each role only sees the entries its
| permissions render, so each role gets a walkthrough of exactly its pages.
|
| `autostart` opens the tour by itself the first time a user lands on the
| page (tracked per user, per browser). The button replays it at any time.
|
*/

return [
    'dashboard' => [
        'routes' => ['dashboard'],
        'autostart' => true,
        'steps' => [
            'welcome' => [],
            'my-leaves' => ['target' => 'dashboard.my-leaves', 'placement' => 'bottom'],
            'notes' => ['target' => 'dashboard.notes', 'placement' => 'bottom'],
            'team' => ['target' => 'dashboard.team', 'placement' => 'start'],
            'news' => ['target' => 'dashboard.news', 'placement' => 'end'],
            'upcoming-leaves' => ['target' => 'dashboard.upcoming-leaves', 'placement' => 'top'],
            'pending-requests' => ['target' => 'dashboard.pending-requests', 'placement' => 'top'],

            // Sidebar: one step per page the user can reach.
            'nav-intro' => ['target' => 'nav', 'placement' => 'end'],
            'nav-statistics' => ['target' => 'nav.statistics', 'placement' => 'end'],
            'nav-org-chart' => ['target' => 'nav.org-chart', 'placement' => 'end'],
            'nav-project-basics' => ['target' => 'nav.project-basics', 'placement' => 'end'],
            'nav-programs' => ['target' => 'nav.programs', 'placement' => 'end'],
            'nav-centers' => ['target' => 'nav.centers', 'placement' => 'end'],
            'nav-employees' => ['target' => 'nav.employees', 'placement' => 'end'],
            'nav-leaves-management' => ['target' => 'nav.leaves-management', 'placement' => 'end'],
            'nav-my-leaves' => ['target' => 'nav.my-leaves', 'placement' => 'end'],
            'nav-archives' => ['target' => 'nav.archives', 'placement' => 'end'],
            'nav-checklists' => ['target' => 'nav.checklists', 'placement' => 'end'],
            'nav-my-items' => ['target' => 'nav.my-items', 'placement' => 'end'],
            'nav-news' => ['target' => 'nav.news', 'placement' => 'end'],
            'nav-badges' => ['target' => 'nav.badges', 'placement' => 'end'],
            'nav-activity-log' => ['target' => 'nav.activity-log', 'placement' => 'end'],
            'nav-access' => ['target' => 'nav.access', 'placement' => 'end'],

            'replay' => ['target' => 'tour.button', 'placement' => 'bottom'],
        ],
    ],

    // The toolbar steps only exist in the chart view; in the directory view
    // they are skipped and the tour covers the header and view switcher.
    'org-chart' => [
        'routes' => ['org.chart'],
        'steps' => [
            'stats' => ['target' => 'org.stats', 'placement' => 'bottom'],
            'views' => ['target' => 'org.views', 'placement' => 'bottom'],
            'search' => ['target' => 'org.search', 'placement' => 'bottom'],
            'filters' => ['target' => 'org.filters', 'placement' => 'bottom'],
            'reporting-line' => ['target' => 'org.reporting-line', 'placement' => 'bottom'],
            'vacancies' => ['target' => 'org.vacancies', 'placement' => 'bottom'],
            'canvas' => ['target' => 'org.canvas', 'placement' => 'top'],
            'node' => ['target' => 'org.node', 'placement' => 'bottom'],
            'controls' => ['target' => 'org.controls', 'placement' => 'bottom'],
            'export' => ['target' => 'org.export', 'placement' => 'bottom'],
        ],
    ],

    'leaves' => [
        'routes' => ['leaves.index'],
        'steps' => [
            'balances' => ['target' => 'leaves.balances', 'placement' => 'bottom'],
            'request' => ['target' => 'leaves.request', 'placement' => 'bottom'],
            'filters' => ['target' => 'leaves.filters', 'placement' => 'bottom'],
            'table' => ['target' => 'leaves.table', 'placement' => 'top'],
        ],
    ],
];
