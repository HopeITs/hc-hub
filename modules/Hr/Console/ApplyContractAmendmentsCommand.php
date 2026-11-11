<?php

declare(strict_types=1);

namespace Modules\Hr\Console;

use Illuminate\Console\Command;
use Modules\Hr\Actions\Contracts\ContractAmendmentService;

/**
 * Daily: apply every approved contract amendment whose effective date has
 * come. Scheduled by HrServiceProvider.
 */
class ApplyContractAmendmentsCommand extends Command
{
    protected $signature = 'contracts:apply-amendments';

    protected $description = 'Apply approved contract amendments whose effective date has come';

    public function handle(ContractAmendmentService $amendments): int
    {
        ['applied' => $applied, 'failed' => $failed, 'cancelled' => $cancelled] = $amendments->applyDue();

        $this->components->info('Applied '.$applied->count().' amendment(s).');

        if ($cancelled->isNotEmpty()) {
            $this->components->info('Cancelled, the contract ended first: '.$cancelled->pluck('id')->implode(', '));
        }

        if ($failed->isNotEmpty()) {
            $this->components->warn('Not applied (see the log): '.$failed->pluck('id')->implode(', '));
        }

        return self::SUCCESS;
    }
}
