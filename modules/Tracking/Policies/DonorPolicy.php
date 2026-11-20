<?php

declare(strict_types=1);

namespace Modules\Tracking\Policies;

use Modules\Core\Models\User;
use Modules\Tracking\Models\Donor;

/**
 * Approving or declining a donor request belongs to the holders of
 * donors.approve, the General Manager (contracts.md §8, D-57). The other
 * donor abilities stay plain permission gates (donors.create / update).
 *
 * view and update repeat the gates of the donor page and its edit form
 * (donors.view / donors.update), so what hangs on a donor record, such as
 * its notes (NotePolicy), follows the same people.
 */
class DonorPolicy
{
    /**
     * Determine whether the user can open the donor.
     */
    public function view(User $user, Donor $donor): bool
    {
        return $user->can('donors.view');
    }

    /**
     * Determine whether the user can edit the donor.
     */
    public function update(User $user, Donor $donor): bool
    {
        return $user->can('donors.update');
    }

    /**
     * Determine whether the user can approve or decline a donor request.
     */
    public function approve(User $user, Donor $donor): bool
    {
        return $user->can('donors.approve');
    }
}
