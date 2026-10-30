<?php

namespace App\Notifications\Concerns;

use App\Enums\NotificationKind;
use App\Enums\NotificationPriority;
use App\Notifications\AppDatabaseChannel;

/**
 * Defaults for an App\Notifications\InboxNotification: delivered to the hub
 * inbox only, a direct action notification at info priority. The class names
 * its module and overrides what differs.
 */
trait RoutesToInbox
{
    /**
     * @return list<class-string>
     */
    public function via(object $notifiable): array
    {
        return [AppDatabaseChannel::class];
    }

    public function inboxKind(): NotificationKind
    {
        return NotificationKind::Action;
    }

    public function inboxPriority(object $notifiable): NotificationPriority
    {
        return NotificationPriority::Info;
    }

    public function isDirect(): bool
    {
        return true;
    }
}
