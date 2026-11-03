<?php

declare(strict_types=1);

namespace App\Actions\Donors;

use App\Enums\DonorApprovalStatus;
use App\Models\Donor;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * The General Manager declines a donor request (D-57). A request no proposal
 * uses is removed; one a proposal already points at stays pending with the
 * reason in its notes, because a referenced donor is never hard-deleted.
 */
final class DeclineDonor
{
    public function handle(Donor $donor, string $reason, User $actor): void
    {
        Gate::forUser($actor)->authorize('approve', $donor);

        $reason = trim($reason);

        if (mb_strlen($reason) < 10) {
            throw ValidationException::withMessages(['reason' => __('donors.give_a_reason_of_at_least_ten_characters')]);
        }

        DB::transaction(function () use ($donor, $reason): void {
            $locked = Donor::query()->whereKey($donor->id)->lockForUpdate()->firstOrFail();

            if ($locked->approval_status !== DonorApprovalStatus::Pending) {
                throw ValidationException::withMessages(['donor' => __('donors.donor_request_already_decided')]);
            }

            $isReferenced = Proposal::query()->where('donor_id', $locked->id)->exists()
                || $locked->donorProposals()->exists();

            // Recorded first: the activity names the requester and the donor
            // label, and must outlive the row it is about.
            $locked->recordActivity('donor.declined', properties: [
                'requested_by' => $locked->requested_by,
                'note' => $reason,
            ]);

            if ($isReferenced) {
                $line = __('donors.declined_note', ['reason' => $reason]);
                $locked->forceFill(['notes' => filled($locked->notes) ? $locked->notes."\n".$line : $line])->save();

                return;
            }

            $locked->delete();
        });
    }
}
