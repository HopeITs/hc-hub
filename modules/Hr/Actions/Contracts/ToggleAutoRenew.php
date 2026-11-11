<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Contracts;

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;

/**
 * Switch automatic renewal on or off (HR only). A terminated contract or a
 * trial never renews.
 */
class ToggleAutoRenew
{
    public function handle(Contract $contract, bool $on, User $actor): Contract
    {
        Gate::forUser($actor)->authorize('manageAutoRenew', $contract);

        if ($on && ! $contract->canAutoRenew()) {
            throw ValidationException::withMessages(['autoRenew' => __('hr_contracts.errors.cannot_auto_renew')]);
        }

        $contract->forceFill(['auto_renew' => $on])->save();

        return $contract;
    }
}
