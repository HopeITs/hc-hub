<?php

declare(strict_types=1);

namespace App\Notifications\Delivery;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Jobs\DeliverNotificationOutbox;
use App\Models\NotificationOutboxEntry;
use App\Notifications\NotificationPayload;
use Modules\Core\Models\User;

/**
 * Writes the push and email copies of one notification to the outbox, each
 * with the moment it may go (now, the next digest, or after quiet hours),
 * and queues the ones due now.
 *
 * Grouping does not cause a second storm: while an unsent-or-recent row for
 * the same person, channel and group_key exists inside
 * notifications.grouping_window_minutes, no new row is written. The inbox
 * row it merged into carries the count, and the copy that goes out is
 * rendered from that row at send time ("... and 2 more updates"). Only an
 * urgent event after a non-urgent one gets its own row.
 */
final class OutboxWriter
{
    public function __construct(private NotificationPreferenceGate $gate) {}

    /**
     * @return list<string> the channels a row was written for
     */
    public function write(User $user, NotificationPayload $payload, string $notificationId): array
    {
        $urgent = $payload->priority === NotificationPriority::Critical;
        $channels = [];

        if ($this->gate->allowsPush($user, $payload->module)
            && WebPushSender::configured()
            && $user->pushSubscriptions()->deliverable()->exists()) {
            $channels[] = NotificationOutboxEntry::PUSH;
        }

        if ($this->gate->allows($user, $payload->module, NotificationChannel::Email) && filled($user->email)) {
            $channels[] = NotificationOutboxEntry::EMAIL;
        }

        $channels = array_values(array_filter(
            $channels,
            fn (string $channel): bool => ! $this->alreadyQueuedForGroup($user, $channel, $payload, $urgent),
        ));

        if ($channels === []) {
            return [];
        }

        $deliverAfter = $this->gate->deliverAt($user, $urgent);
        $now = now();

        NotificationOutboxEntry::query()->insertOrIgnore(array_map(fn (string $channel): array => [
            'user_id' => $user->getKey(),
            'notification_id' => $notificationId,
            'channel' => $channel,
            'category' => $payload->module,
            'group_key' => $payload->groupKey,
            'urgent' => $urgent,
            'payload' => json_encode([
                'module' => $payload->module,
                'priority' => $payload->priority->value,
                'key' => $payload->key,
                'params' => $payload->params,
                'count' => 1,
                'link' => $payload->link,
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'deliver_after' => $deliverAfter,
            'attempts' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ], $channels));

        if ($deliverAfter->lessThanOrEqualTo($now)) {
            $dueIds = NotificationOutboxEntry::query()
                ->where('notification_id', $notificationId)
                ->whereIn('channel', $channels)
                ->whereNull('sent_at')
                ->pluck('id')
                ->all();

            if ($dueIds !== []) {
                DeliverNotificationOutbox::dispatch($dueIds)->afterCommit();
            }
        }

        return $channels;
    }

    private function alreadyQueuedForGroup(User $user, string $channel, NotificationPayload $payload, bool $urgent): bool
    {
        if ($payload->groupKey === null) {
            return false;
        }

        return NotificationOutboxEntry::query()
            ->where('user_id', $user->getKey())
            ->where('channel', $channel)
            ->where('group_key', $payload->groupKey)
            ->where('created_at', '>=', now()->subMinutes((int) config('notifications.grouping_window_minutes')))
            ->when($urgent, fn ($query) => $query->where('urgent', true))
            ->exists();
    }
}
