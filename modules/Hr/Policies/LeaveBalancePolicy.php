<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Support\Settings\HrAccess;

/**
 * Leave balances: HR with leaves.manage-balances reads the balance list and
 * adds to or takes from an annual balance, inside its governorates. The
 * owner reads their own (the My leaves page). Callers load contract.employee.
 */
class LeaveBalancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('leaves.manage-balances');
    }

    public function view(User $user, LeaveBalance $balance): bool
    {
        $employee = $balance->contract?->employee;

        return ($user->employee_id !== null && (int) $user->employee_id === (int) $employee?->id)
            || ($user->can('leaves.manage-balances') && $this->inScope($user, $employee?->city_id));
    }

    public function update(User $user, LeaveBalance $balance): bool
    {
        return $user->can('leaves.manage-balances')
            && $this->inScope($user, $balance->contract?->employee?->city_id);
    }

    private function inScope(User $user, ?int $cityId): bool
    {
        $cityIds = HrAccess::for($user)->cityIds();

        return $cityIds === null || ($cityId !== null && in_array($cityId, $cityIds, true));
    }
}
