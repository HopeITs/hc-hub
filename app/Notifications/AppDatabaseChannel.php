<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Models\UserNotification;
use App\Notifications\Delivery\NotificationPreferenceGate;
use App\Notifications\Delivery\OutboxWriter;
use Modules\Core\Models\User;
use Throwable;

/**
 * Stores an AppNotification in the inbox (merging it into a recent unread row
 * when the grouper says so), refreshes the owner's unread counts, pushes it
 * to their open tabs, then writes its push and email copies to the outbox.
 *
 * The person's notification settings decide each channel. With "in the app"
 * off for the module nothing is stored, but push and email still go out.
 * A failure while reading the settings is reported and the notification is
 * stored as before: settings must never lose a notification.
 */
final class AppDatabaseChannel
{
    public function __construct(
        private NotificationGrouper $grouper,
        private UnreadCounts $counts,
        private NotificationBroadcaster $broadcaster,
        private NotificationPreferenceGate $gate,
        private OutboxWriter $outbox,
    ) {}

    public function send(User $notifiable, AppNotification $notification): ?UserNotification
    {
        try {
            $inApp = $this->gate->allows($notifiable, $notification->payload->module, NotificationChannel::InApp);
        } catch (Throwable $e) {
            report($e);

            return $this->store($notifiable, $notification);
        }

        $row = $inApp ? $this->store($notifiable, $notification) : null;

        // The outbox points at the inbox row the notification landed in, so a
        // copy whose row is read first is dropped. An urgent event merged into
        // an earlier row keeps its own id: its copy must not collide with the
        // earlier copy of that row.
        $outboxId = $row === null || ($row->id !== $notification->id && $notification->payload->priority === NotificationPriority::Critical)
            ? $notification->id
            : $row->id;

        try {
            $this->outbox->write($notifiable, $notification->payload, $outboxId);
        } catch (Throwable $e) {
            report($e);
        }

        return $row;
    }

    private function store(User $notifiable, AppNotification $notification): UserNotification
    {
        [$row] = $this->grouper->store($notifiable, $notification->payload, $notification->id);

        $this->counts->forget($notifiable->id);
        $this->broadcaster->push($notifiable, $row);

        return $row;
    }
}
