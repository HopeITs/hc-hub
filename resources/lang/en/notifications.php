<?php

return [
    'inbox' => 'Notifications',
    'bell_label' => '{0} Notifications|{1} Notifications, :count unread|[2,*] Notifications, :count unread',
    'critical_count' => '{1} :count critical|[2,*] :count critical',
    'caught_up' => 'You are all caught up',
    'full_page' => 'Open all notifications',
    'settings_link' => 'Notification settings',
    'untitled' => 'Notification',
    'more_updates' => '{1}(+:count more update)|[2,*](+:count more updates)',

    'tabs' => [
        'all' => 'All',
        'unread' => 'Unread',
        'alerts' => 'Alerts',
    ],

    'sections' => [
        'pinned' => 'Needs attention',
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'earlier' => 'Earlier',
    ],

    'priorities' => [
        'info' => 'Info',
        'important' => 'Important',
        'critical' => 'Critical',
    ],

    'kinds' => [
        'action' => 'Activity',
        'alert' => 'Alert',
        'digest' => 'Summary',
    ],

    'modules' => [
        'hub' => 'General',
        'leaves' => 'Leaves',
        'contracts' => 'Contracts',
        'centers' => 'Centers',
        'checklists' => 'Checklists',
        'archive' => 'Archive',
        'employees' => 'Employees',
    ],

    'mark_read' => 'Mark as read',
    'mark_unread' => 'Mark as unread',
    'snooze' => 'Remind me later',
    'unsnooze' => 'Show now',
    'snoozed_until' => 'Snoozed until :time',
    'snooze_presets' => [
        '1h' => 'In an hour',
        'tomorrow' => 'Tomorrow morning',
        'next_week' => 'Next week',
    ],

    'formats' => [
        'date' => 'j M Y',
        'datetime' => 'j M Y, g:i A',
    ],

    'settings' => [
        'title' => 'Notification settings',
        'intro' => 'Choose which general notifications reach your bell.',
        'mutes' => 'General notifications',
        'toggle' => 'Notifications about :module',
        'state_on' => 'You receive these',
        'state_muted' => 'Muted',
        'muted' => 'Muted. Notifications addressed to you still arrive.',
        'unmuted' => 'You will receive these notifications again.',
        'nothing_to_mute' => 'There is nothing to mute yet.',
        'direct_note' => 'Notifications addressed to you personally, such as a request waiting for your decision, always arrive.',
    ],
];
