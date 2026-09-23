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
|
*/

return [
    'dashboard' => [
        'routes' => ['dashboard'],
        'steps' => [
            'welcome' => [],
            'my-leaves' => ['target' => 'dashboard.my-leaves', 'placement' => 'bottom'],
            'notes' => ['target' => 'dashboard.notes', 'placement' => 'bottom'],
            'team' => ['target' => 'dashboard.team', 'placement' => 'start'],
            'news' => ['target' => 'dashboard.news', 'placement' => 'end'],
            'upcoming-leaves' => ['target' => 'dashboard.upcoming-leaves', 'placement' => 'top'],
            'pending-requests' => ['target' => 'dashboard.pending-requests', 'placement' => 'top'],
            'replay' => ['target' => 'tour.button', 'placement' => 'bottom'],
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
