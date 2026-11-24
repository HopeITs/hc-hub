<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Notifications\Delivery\OutboxDispatcher;
use Illuminate\Console\Command;

/**
 * Every minute: sends the push and email copies whose moment has come (the
 * end of quiet hours, the next working day, an hourly or daily digest), and
 * any instant copy whose job did not run.
 */
class DispatchDueNotifications extends Command
{
    /**
     * @var string
     */
    protected $signature = 'notifications:dispatch-due {--limit=500 : The most outbox rows to handle in one run}';

    /**
     * @var string
     */
    protected $description = 'Send the push and email notifications that are due';

    public function handle(OutboxDispatcher $dispatcher): int
    {
        $handled = $dispatcher->dispatchDue(max(1, (int) $this->option('limit')));

        $this->components->info("Handled {$handled} outbox row(s).");

        return self::SUCCESS;
    }
}
