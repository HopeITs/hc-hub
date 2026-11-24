<?php

declare(strict_types=1);

namespace Modules\Hr\Console;

use Illuminate\Console\Command;
use Modules\Hr\Support\Contracts\ProbationReviews;

/**
 * Starts a review for every running trial that entered its last third, and
 * reminds the direct manager weekly until they answer. Idempotent: the unique
 * contract id and the delivery ledger make a second run, a retry or a second
 * worker change nothing.
 *
 * Scheduled at 21:20, before the contract amendments (21:25), deactivation
 * (21:30) and auto-renewal (21:35): a trial ending tonight still gets its
 * review, and the sweeps that follow never see a trial to roll over.
 */
class StartProbationReviewsCommand extends Command
{
    protected $signature = 'probation:start-reviews';

    protected $description = 'Start the last-third review of trial contracts and remind the managers who have not answered';

    public function handle(ProbationReviews $reviews): int
    {
        $started = $reviews->startDue();
        $reminded = $reviews->remindPending();

        $this->components->info("Started {$started} probation review(s); sent {$reminded} reminder(s).");

        return self::SUCCESS;
    }
}
