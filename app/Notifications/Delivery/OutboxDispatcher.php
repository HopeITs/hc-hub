<?php

declare(strict_types=1);

namespace App\Notifications\Delivery;

use App\Enums\NotificationChannel;
use App\Models\NotificationOutboxEntry;
use App\Models\UserNotification;
use App\Notifications\NotificationPresenter;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Core\Models\User;
use Throwable;

/**
 * Sends outbox rows (push or email) for the job and the every-minute command
 * alike, so both follow the same rules (ported from HR):
 *
 *   - a row whose inbox notification was already read is dropped
 *     ('read_in_app'): no late push for something already seen;
 *   - the preferences are asked again, since they may have changed while the
 *     row waited ('preferences_changed', or moved later);
 *   - several rows of one person and channel become one summary (the hourly
 *     and daily digests), urgent rows go one by one.
 *
 * Each person+channel is handled under a lock and rows are re-read inside
 * it, so the job and the command never send the same row twice. Rows are
 * rendered in the reader's language only now, from the inbox row when it
 * still exists (so "and 2 more updates" is current) or from the payload.
 */
final class OutboxDispatcher
{
    /**
     * Email attempts before a row is given up (push is not retried: the push
     * service already queues it).
     */
    public const int MAX_EMAIL_ATTEMPTS = 3;

    public function __construct(
        private NotificationPreferenceGate $gate,
        private WebPushSender $push,
        private NotificationPresenter $presenter,
    ) {}

    /**
     * Everything due now, oldest first. Returns how many rows were handled.
     */
    public function dispatchDue(int $limit = 500): int
    {
        $entries = NotificationOutboxEntry::query()
            ->due()
            ->with('user')
            ->orderBy('deliver_after')
            ->limit($limit)
            ->get();

        $this->deliver($entries, retryLocked: false);

        return $entries->count();
    }

    /**
     * Deliver any set of rows, grouped by person and channel.
     *
     * @param  Collection<int, NotificationOutboxEntry>  $entries
     */
    public function deliver(Collection $entries, bool $retryLocked = true): void
    {
        $entries->groupBy(fn (NotificationOutboxEntry $entry): string => $entry->user_id.'|'.$entry->channel)
            ->each(function (Collection $group) use ($retryLocked): void {
                $first = $group->first();

                if ($first->user === null) {
                    return;
                }

                try {
                    $this->deliverGroup($first->user, $first->channel, $group);
                } catch (LockTimeoutException $e) {
                    // Someone else is sending this person's rows right now:
                    // the job retries, the command finds them next minute.
                    if ($retryLocked) {
                        throw $e;
                    }
                }
            });
    }

    /**
     * @param  Collection<int, NotificationOutboxEntry>  $entries
     */
    public function deliverGroup(User $user, string $channel, Collection $entries): void
    {
        Cache::lock("notification-outbox:{$user->getKey()}:{$channel}", 120)
            ->block(10, function () use ($user, $channel, $entries): void {
                $pending = NotificationOutboxEntry::query()
                    ->whereKey($entries->modelKeys())
                    ->where('user_id', $user->getKey())
                    ->whereNull('sent_at')
                    ->whereNull('failed_at')
                    ->get();

                $pending = $this->dropRead($user, $pending);
                $pending = $this->dropDisallowed($user, $channel, $pending);
                $pending = $this->postponeQuiet($user, $pending);

                if ($pending->isEmpty()) {
                    return;
                }

                [$urgent, $normal] = $pending->partition(fn (NotificationOutboxEntry $entry): bool => $entry->urgent);

                foreach ($urgent as $entry) {
                    $this->send($user, $channel, collect([$entry]));
                }

                if ($normal->isNotEmpty()) {
                    $this->send($user, $channel, $normal->values());
                }
            });
    }

    /**
     * One row as the reader sees it, in their language.
     *
     * @return array{module: string, title: string, body: string, url: string, tag: string}
     */
    public function render(User $user, NotificationOutboxEntry $entry, ?UserNotification $inboxRow = null): array
    {
        $locale = WebPushSender::localeFor($user);
        $payload = $entry->payload;
        $data = $inboxRow !== null ? (array) $inboxRow->data : $payload;

        return [
            'module' => $entry->category,
            'title' => $this->presenter->moduleLabel($entry->category, $locale),
            'body' => $this->presenter->sentence($data, $locale),
            'url' => $inboxRow !== null ? route('notifications.open', $inboxRow->id) : route('notifications.index'),
            'tag' => (string) ($entry->group_key ?? $entry->notification_id ?? $entry->getKey()),
        ];
    }

    /**
     * @param  Collection<int, NotificationOutboxEntry>  $entries
     * @return Collection<int, NotificationOutboxEntry>
     */
    private function dropRead(User $user, Collection $entries): Collection
    {
        $ids = $entries->pluck('notification_id')->filter()->values()->all();

        if ($ids === []) {
            return $entries;
        }

        $read = UserNotification::query()->forUser($user)->whereKey($ids)->whereNotNull('read_at')->pluck('id')->all();

        return $entries->reject(function (NotificationOutboxEntry $entry) use ($read): bool {
            if (! in_array($entry->notification_id, $read, true)) {
                return false;
            }

            $this->markSent($entry, 'read_in_app');

            return true;
        })->values();
    }

    /**
     * @param  Collection<int, NotificationOutboxEntry>  $entries
     * @return Collection<int, NotificationOutboxEntry>
     */
    private function dropDisallowed(User $user, string $channel, Collection $entries): Collection
    {
        return $entries->reject(function (NotificationOutboxEntry $entry) use ($user, $channel): bool {
            $allowed = $channel === NotificationOutboxEntry::EMAIL
                ? $this->gate->allows($user, $entry->category, NotificationChannel::Email)
                : $this->gate->allowsPush($user, $entry->category);

            if ($allowed) {
                return false;
            }

            $this->markSent($entry, 'preferences_changed');

            return true;
        })->values();
    }

    /**
     * Quiet hours or working days set after the row was written move it later
     * instead of sending it into the night.
     *
     * @param  Collection<int, NotificationOutboxEntry>  $entries
     * @return Collection<int, NotificationOutboxEntry>
     */
    private function postponeQuiet(User $user, Collection $entries): Collection
    {
        $now = now();

        return $entries->reject(function (NotificationOutboxEntry $entry) use ($user, $now): bool {
            $opens = $this->gate->windowOpensAt($user, $entry->urgent, $now);

            if ($opens->lessThanOrEqualTo($now)) {
                return false;
            }

            $entry->forceFill(['deliver_after' => $opens])->save();

            return true;
        })->values();
    }

    /**
     * @param  Collection<int, NotificationOutboxEntry>  $entries
     */
    private function send(User $user, string $channel, Collection $entries): void
    {
        if ($channel === NotificationOutboxEntry::EMAIL) {
            $this->sendEmail($user, $entries);

            return;
        }

        if ($channel !== NotificationOutboxEntry::PUSH) {
            $entries->each(fn (NotificationOutboxEntry $entry) => $this->markFailed($entry, 'unknown_channel'));

            return;
        }

        if (! WebPushSender::configured()) {
            $entries->each(fn (NotificationOutboxEntry $entry) => $this->markFailed($entry, 'push_not_configured'));

            return;
        }

        $modules = $entries->pluck('category')->values()->all();
        $urgent = $entries->contains(fn (NotificationOutboxEntry $entry): bool => $entry->urgent);
        $payload = $entries->count() === 1
            ? $this->renderAll($user, $entries)[0]
            : $this->summaryPayload($user, $entries->count());

        try {
            $accepted = $this->push->sendToUserForModules($user, $payload, $modules, $urgent);
        } catch (Throwable $e) {
            report($e);
            $entries->each(fn (NotificationOutboxEntry $entry) => $this->markFailed($entry, Str::limit($e->getMessage(), 250, '')));

            return;
        }

        // Not retried when nobody accepted it: the devices' own failure
        // counters record why, and a retry could repeat it on the ones that
        // did take it.
        $entries->each(fn (NotificationOutboxEntry $entry) => $this->markSent($entry, $accepted === 0 ? 'no_device_accepted' : null));
    }

    /**
     * @param  Collection<int, NotificationOutboxEntry>  $entries
     */
    private function sendEmail(User $user, Collection $entries): void
    {
        if (blank($user->email)) {
            $entries->each(fn (NotificationOutboxEntry $entry) => $this->markFailed($entry, 'no_email_address'));

            return;
        }

        try {
            $items = array_map(
                fn (array $item): array => ['module' => $item['module'], 'title' => $item['title'], 'body' => $item['body'], 'url' => $item['url']],
                $this->renderAll($user, $entries),
            );

            $user->notifyNow((new NotificationEmail($items))->locale(WebPushSender::localeFor($user)));
        } catch (Throwable $e) {
            report($e);

            foreach ($entries as $entry) {
                $attempts = $entry->attempts + 1;
                $entry->forceFill([
                    'attempts' => $attempts,
                    'error' => Str::limit($e->getMessage(), 250, ''),
                    // Left unsent below the limit, so the next run tries again.
                    'failed_at' => $attempts >= self::MAX_EMAIL_ATTEMPTS ? now() : null,
                ])->save();
            }

            return;
        }

        $entries->each(fn (NotificationOutboxEntry $entry) => $this->markSent($entry));
    }

    /**
     * @param  Collection<int, NotificationOutboxEntry>  $entries
     * @return list<array{module: string, title: string, body: string, url: string, tag: string}>
     */
    private function renderAll(User $user, Collection $entries): array
    {
        $inboxRows = UserNotification::query()
            ->forUser($user)
            ->whereKey($entries->pluck('notification_id')->filter()->values()->all())
            ->get()
            ->keyBy('id');

        return $entries->map(fn (NotificationOutboxEntry $entry): array => $this->render(
            $user,
            $entry,
            $entry->notification_id === null ? null : $inboxRows->get($entry->notification_id),
        ))->values()->all();
    }

    /**
     * @return array{title: string, body: string, url: string, tag: string}
     */
    private function summaryPayload(User $user, int $count): array
    {
        $locale = WebPushSender::localeFor($user);

        return [
            'title' => (string) __('notification_delivery.summary.title', ['count' => $count], $locale),
            'body' => (string) __('notification_delivery.summary.body', ['count' => $count], $locale),
            'url' => route('notifications.index'),
            'tag' => 'summary',
        ];
    }

    private function markSent(NotificationOutboxEntry $entry, ?string $error = null): void
    {
        $entry->forceFill(['sent_at' => now(), 'attempts' => $entry->attempts + 1, 'error' => $error])->save();
    }

    private function markFailed(NotificationOutboxEntry $entry, string $error): void
    {
        $entry->forceFill(['failed_at' => now(), 'attempts' => $entry->attempts + 1, 'error' => $error])->save();
    }
}
