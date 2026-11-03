<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Donor;
use App\Models\User;

/**
 * Approving or declining a donor request belongs to the holders of
 * donors.approve, the General Manager (contracts.md §8, D-57). The other
 * donor abilities stay plain permission gates (donors.create / update).
 */
class DonorPolicy
{
    /**
     * Determine whether the user can approve or decline a donor request.
     */
    public function approve(User $user, Donor $donor): bool
    {
        return $user->can('donors.approve');
    }
}
