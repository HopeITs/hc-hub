<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\AnnualPlans;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Tracking\Enums\ActivityPlanStatus;
use Modules\Tracking\Models\ActivityPlan;
use Modules\Tracking\Support\PlanNotifier;

/**
 * Step 4: send the plan to MEAL. Every activity must be Ready. The plan is
 * locked while MEAL reviews it; a previous send-back reason is cleared.
 */
final class SubmitActivityPlan
{
    public function handle(User $user, ActivityPlan $plan): ActivityPlan
    {
        Gate::forUser($user)->authorize('update', $plan);

        return DB::transaction(function () use ($user, $plan): ActivityPlan {
            $locked = ActivityPlan::query()->lockForUpdate()->findOrFail($plan->id);
            $items = $locked->items()->get(['id', 'is_ready']);

            if ($items->isEmpty() || $items->contains('is_ready', false)) {
                throw ValidationException::withMessages(['plan' => __('annual_plans.not_all_ready')]);
            }

            $locked->forceFill([
                'status' => ActivityPlanStatus::InReview,
                'step' => 3,
                'submitted_at' => now(),
                'submitted_by' => $user->id,
                'sent_back_reason' => null,
            ])->save();
            PlanNotifier::notifyReviewers($locked, $user);

            return $locked;
        });
    }
}
