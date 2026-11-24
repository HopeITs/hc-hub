<?php

declare(strict_types=1);

namespace Modules\Tracking\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Modules\Tracking\Notifications\Alerts\PlanMilestoneScanner;

class ScanPlanMilestones extends Command
{
    protected $signature = 'annual-plans:scan-milestones';

    protected $description = 'Send milestone reminders and missed alerts';

    public function handle(PlanMilestoneScanner $scanner): int
    {
        $scanner->scan(CarbonImmutable::now());

        return self::SUCCESS;
    }
}
