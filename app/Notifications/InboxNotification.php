<?php

namespace App\Notifications;

use App\Enums\NotificationKind;
use App\Enums\NotificationPriority;

/**
 * A notification class that builds its own data (toDatabase / toArray) and
 * tells AppDatabaseChannel how to file it in the inbox. Use the
 * Concerns\RoutesToInbox trait for the defaults.
 */
interface InboxNotification
{
    /** A key of config('notifications.inbox_modules'). */
    public function inboxModule(): string;

    public function inboxKind(): NotificationKind;

    public function inboxPriority(object $notifiable): NotificationPriority;

    /** Addressed to this reader personally: a module mute does not silence it. */
    public function isDirect(): bool;
}
