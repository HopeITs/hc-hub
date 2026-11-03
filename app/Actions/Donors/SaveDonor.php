<?php

declare(strict_types=1);

namespace App\Actions\Donors;

use App\Enums\DonorApprovalStatus;
use App\Models\Donor;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Record a new donor or update an existing one from already-validated input.
 *
 * A new donor recorded by someone who cannot approve donors is a pending
 * request (D-57); editing never changes the approval status.
 */
class SaveDonor
{
    /**
     * @param  array<string, mixed>  $validated  keyed by the donor form's property names
     */
    public function handle(?Donor $existing, array $validated, User $actor): Donor
    {
        Gate::authorize($existing === null ? 'donors.create' : 'donors.update');

        $donor = $existing ?? new Donor;

        if ($existing === null) {
            $approves = $actor->can('donors.approve');

            $donor->forceFill([
                'approval_status' => $approves ? DonorApprovalStatus::Approved : DonorApprovalStatus::Pending,
                'requested_by' => $approves ? null : $actor->id,
                'approved_by' => $approves ? $actor->id : null,
                'approved_at' => $approves ? now() : null,
            ]);
        }

        $donor->fill([
            'name' => $validated['name'],
            'type' => filled($validated['donorType']) ? $validated['donorType'] : null,
            'contact_person' => filled($validated['contactPerson']) ? $validated['contactPerson'] : null,
            'email' => filled($validated['email']) ? $validated['email'] : null,
            'phone' => filled($validated['phone']) ? $validated['phone'] : null,
            'country' => filled($validated['country']) ? $validated['country'] : null,
            'notes' => filled($validated['notes']) ? $validated['notes'] : null,
            'last_contacted_on' => filled($validated['lastContactedOn']) ? $validated['lastContactedOn'] : null,
            'next_step' => filled($validated['nextStep']) ? $validated['nextStep'] : null,
            'next_step_due_on' => filled($validated['nextStepDueOn']) ? $validated['nextStepDueOn'] : null,
        ])->save();

        return $donor;
    }
}
