<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\StrategicPlans;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\User;
use Modules\Tracking\Enums\StrategicPlanStatus;
use Modules\Tracking\Models\StrategicPlan;

/**
 * Take an approved strategic plan back to a draft so the MEAL manager can
 * revise it; it then needs the program manager's approval again.
 */
final class ReopenStrategicPlan
{
    public function handle(User $user, StrategicPlan $plan): StrategicPlan
    {
        Gate::forUser($user)->authorize('reopen', $plan);

        return DB::transaction(function () use ($plan): StrategicPlan {
            $locked = StrategicPlan::query()->lockForUpdate()->findOrFail($plan->id);

            if ($locked->status === StrategicPlanStatus::Approved) {
                $locked->forceFill(['status' => StrategicPlanStatus::Draft, 'approved_at' => null, 'approved_by' => null])->save();
            }

            return $locked;
        });
    }
}
