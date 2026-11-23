<?php

declare(strict_types=1);

namespace Modules\Hr\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\MailLists\MailListSync;

/**
 * People whose contract has just started are queued as pending on the mail lists they belong on.
 * A contract made ahead of its start date (or by an import or command, which never queue) is not
 * caught when it is created, because the list rules only count contracts running today. This runs
 * daily and looks back a few days, so it also covers a missed run. Nobody is queued twice.
 */
class QueueMailListStartersCommand extends Command
{
    protected $signature = 'mail-lists:queue-starters {--days=3 : How many days back a contract may have started}';

    protected $description = 'Queue employees whose contract just started onto the mail lists their rules match (pending, for the control panel)';

    public function handle(MailListSync $sync): int
    {
        $since = now()->subDays(max(0, (int) $this->option('days')))->startOfDay();

        $employees = Employee::query()
            ->whereHas('account')
            ->whereHas('contracts', fn (Builder $contracts): Builder => $contracts->activeNow()->whereDate('start_date', '>=', $since))
            ->with('account')
            ->get();

        $queued = 0;

        foreach ($employees as $employee) {
            $queued += $sync->queueForPerson($employee);
        }

        $this->components->info("{$employees->count()} starters, {$queued} list additions queued.");

        return self::SUCCESS;
    }
}
