<?php

namespace App\Notifications;

use App\Enums\NotificationKind;
use App\Enums\NotificationPriority;
use App\Models\NotificationMute;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;

/**
 * The one database channel of the hub (tracking's AppDatabaseChannel, plan
 * decision 11): every module's notification lands in the extended
 * `notifications` table with its module, kind, priority and subject, so one
 * bell shows them all. It is also bound as Laravel's `database` driver, so a
 * job queued before the switch (its channel list still says `database`) is
 * stored the same way.
 *
 * A notification that is not addressed to the reader personally is dropped
 * when the reader muted its module. Grouping may merge it into a recent
 * unread row instead of adding one.
 */
class AppDatabaseChannel extends DatabaseChannel
{
    public function __construct(private readonly NotificationGrouper $grouper) {}

    /**
     * @param  mixed  $notifiable
     */
    public function send($notifiable, Notification $notification): ?UserNotification
    {
        if (! $notifiable instanceof User) {
            return null;
        }

        $payload = $this->payload($notifiable, $notification);

        if (! $payload->direct && $this->muted($notifiable, $payload->module)) {
            return null;
        }

        $type = method_exists($notification, 'databaseType')
            ? (string) $notification->databaseType($notifiable)
            : $notification::class;

        [$row] = $this->grouper->store($notifiable, $payload, $notification->id, $type);

        return $row;
    }

    private function payload(User $notifiable, Notification $notification): NotificationPayload
    {
        if ($notification instanceof AppNotification) {
            return $notification->payload;
        }

        /** @var array<string, mixed> $data */
        $data = $this->getData($notifiable, $notification);
        $subject = is_array($data['subject'] ?? null) ? $data['subject'] : [];
        $inbox = $notification instanceof InboxNotification ? $notification : null;

        return new NotificationPayload(
            kind: $inbox?->inboxKind() ?? NotificationKind::Action,
            module: $inbox?->inboxModule() ?? 'hub',
            priority: $inbox?->inboxPriority($notifiable) ?? NotificationPriority::Info,
            subjectType: is_string($subject['type'] ?? null) ? $subject['type'] : null,
            subjectId: is_numeric($subject['id'] ?? null) ? (int) $subject['id'] : null,
            content: $data,
            direct: $inbox?->isDirect() ?? true,
        );
    }

    private function muted(User $user, string $module): bool
    {
        return NotificationMute::query()->where('user_id', $user->getKey())->where('module', $module)->exists();
    }
}
