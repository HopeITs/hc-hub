<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\Donors;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Tracking\Enums\DonorApprovalStatus;
use Modules\Tracking\Models\Donor;

/**
 * The General Manager approves a donor Public Relations requested (D-57).
 */
final class ApproveDonor
{
    public function handle(Donor $donor, User $actor): Donor
    {
        Gate::forUser($actor)->authorize('approve', $donor);

        return DB::transaction(function () use ($donor, $actor): Donor {
            $locked = Donor::query()->whereKey($donor->id)->lockForUpdate()->firstOrFail();

            if ($locked->approval_status !== DonorApprovalStatus::Pending) {
                throw ValidationException::withMessages(['donor' => __('donors.donor_request_already_decided')]);
            }

            $locked->forceFill([
                'approval_status' => DonorApprovalStatus::Approved,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ])->save();

            $locked->recordActivity('donor.approved', properties: ['requested_by' => $locked->requested_by]);

            return $locked;
        });
    }
}
