<?php

declare(strict_types=1);

namespace Modules\Hr\Console;

use Illuminate\Console\Command;
use Modules\Hr\Support\CriticalPositions\CriticalPositionAlerts;
use Modules\Hr\Support\Journeys\DeliveryLedger;

/** Tell HR and the direct manager about critical positions that need a replacement. */
class NotifyCriticalPositionsCommand extends Command
{
    protected $signature = 'positions:notify-critical';

    protected $description = 'Notify HR and the direct manager when a critical position has no holder';

    public function handle(CriticalPositionAlerts $alerts, DeliveryLedger $ledger): int
    {
        $this->components->info('Sent '.$alerts->run($ledger).' critical position alert(s).');

        return self::SUCCESS;
    }
}
