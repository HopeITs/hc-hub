<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\NotificationOutboxEntry;
use App\Notifications\Delivery\OutboxDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sends the outbox rows that were due the moment they were written, so an
 * instant notification does not wait for the every-minute command. Rows
 * already sent by the command are skipped inside the dispatcher's lock.
 */
final class DeliverNotificationOutbox implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  list<int>  $entryIds
     */
    public function __construct(public array $entryIds) {}

    public function handle(OutboxDispatcher $dispatcher): void
    {
        $entries = NotificationOutboxEntry::query()
            ->whereKey($this->entryIds)
            ->whereNull('sent_at')
            ->whereNull('failed_at')
            ->with('user')
            ->get();

        $dispatcher->deliver($entries);
    }
}
