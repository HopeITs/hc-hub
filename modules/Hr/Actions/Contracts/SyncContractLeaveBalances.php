<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Contracts;

use Illuminate\Support\Facades\DB;
use Modules\Hr\Models\Contract;
use Modules\Hr\Support\Leave\LeaveBalanceService;
use Throwable;

/**
 * Keep a contract's leave balances in step with its terms: a contract with
 * none gets its full set (a new contract, a renewal, an upgraded draft), one
 * that has them is re-priced (an edit). The one entry point SaveContract and
 * the renew modal use, as hc-hr's contract forms did.
 *
 * Runs in a savepoint: a balance that cannot be priced (no annual-leave rule
 * on file) is reported and leaves the contract saved, as the nightly
 * auto-renewal does.
 */
class SyncContractLeaveBalances
{
    public function __construct(private LeaveBalanceService $leaveBalances) {}

    public function handle(Contract $contract): void
    {
        try {
            DB::transaction(function () use ($contract): void {
                if ($contract->leaveBalances()->exists()) {
                    $this->leaveBalances->update($contract);

                    return;
                }

                $this->leaveBalances->create($contract);
            });
        } catch (Throwable $e) {
            report($e);
        }
    }
}
