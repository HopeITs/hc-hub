<?php

declare(strict_types=1);

namespace Modules\Hr\Console\Journeys;

use Illuminate\Console\Command;
use Modules\Hr\Support\Journeys\RenewalJourneys;

/**
 * Renewal journey (DEC-09 increment 2): starts one journey per contract that
 * ends within 30 days and can renew, then settles the open ones the system
 * finished since (a renewal with its leave balances, a termination).
 * Idempotent: the unique contract id and the expected-state checks make a
 * second run change nothing.
 */
class TrackRenewalJourneys extends Command
{
    protected $signature = 'hr:track-renewal-journeys';

    protected $description = 'Start renewal journeys 30 days before a contract ends, and settle the ones the system finished';

    public function handle(RenewalJourneys $journeys): int
    {
        $started = $journeys->startDue();
        $settled = $journeys->settleOpen();

        $this->info("Started {$started} renewal journey(s); settled {$settled}.");

        return self::SUCCESS;
    }
}
