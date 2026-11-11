<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Contracts;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Support\Contracts\PositionSeats;

/**
 * Delete a contract and give back its seat. A contract other records still
 * point at (leave balances, amendments) is refused by the database: false.
 */
class DeleteContract
{
    public function handle(Contract $contract, User $actor): bool
    {
        Gate::forUser($actor)->authorize('delete', $contract);

        try {
            DB::transaction(function () use ($contract): void {
                $held = PositionSeats::holdsSeat($contract->type, $contract->is_active);
                $positionId = $contract->position_id;

                $contract->payTiers()->delete();
                $contract->delete();

                if ($held) {
                    PositionSeats::move($positionId, null);
                }
            });
        } catch (QueryException) {
            return false;
        }

        return true;
    }
}
