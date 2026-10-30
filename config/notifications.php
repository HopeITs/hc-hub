<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Inbox modules
    |--------------------------------------------------------------------------
    |
    | Every value the `notifications.module` column may hold, in the order the
    | bell's module chips and the mute settings list them. `icon` is the
    | Heroicon the row and chip are drawn with, `label` the lang key of its
    | name. A module is `mutable` when it sends notifications that are not
    | addressed to the reader personally (announcements, summaries); only
    | those can be muted, a direct notification always arrives. A module that
    | ports its notifications adds its own entries here.
    */
    'inbox_modules' => [
        'hub' => ['icon' => 'bell', 'label' => 'notifications.modules.hub', 'mutable' => false],
        'leaves' => ['icon' => 'calendar-days', 'label' => 'notifications.modules.leaves', 'mutable' => false],
        'contracts' => ['icon' => 'document-text', 'label' => 'notifications.modules.contracts', 'mutable' => true],
        'centers' => ['icon' => 'building-office-2', 'label' => 'notifications.modules.centers', 'mutable' => true],
        'checklists' => ['icon' => 'clipboard-document-check', 'label' => 'notifications.modules.checklists', 'mutable' => false],
        'archive' => ['icon' => 'folder', 'label' => 'notifications.modules.archive', 'mutable' => false],
        'employees' => ['icon' => 'user-group', 'label' => 'notifications.modules.employees', 'mutable' => false],
    ],

    /*
    |--------------------------------------------------------------------------
    | Grouping window
    |--------------------------------------------------------------------------
    |
    | An unread notification with the same group key, touched within this many
    | minutes, absorbs a new one instead of a second row appearing.
    */
    'grouping_window_minutes' => (int) env('NOTIFICATIONS_GROUPING_WINDOW', 30),

    /*
    |--------------------------------------------------------------------------
    | First working day of the week
    |--------------------------------------------------------------------------
    |
    | Carbon day number (0 = Sunday) the "next week" snooze wakes on, 08:00.
    */
    'week_starts_on' => 0,

    /*
    |--------------------------------------------------------------------------
    | Admin-editable defaults
    |--------------------------------------------------------------------------
    |
    | Defaults for the values admins can override in `notification_settings`
    | (App\Notifications\NotificationSettings). Tracking's thresholds, working
    | days and digest times are added here when it is ported.
    */
    'defaults' => [],

];
