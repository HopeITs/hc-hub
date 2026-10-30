<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * A notification described entirely by its payload (tracking style). It is
 * sent from already-queued work, so it does not queue itself.
 */
final class AppNotification extends Notification
{
    public function __construct(public NotificationPayload $payload) {}

    /**
     * @return list<class-string>
     */
    public function via(object $notifiable): array
    {
        return [AppDatabaseChannel::class];
    }
}
