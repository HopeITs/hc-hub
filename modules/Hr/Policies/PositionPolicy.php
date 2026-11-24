<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Employees\HrAccess;

/**
 * Who may see and change a position. A regional HR role is limited to the
 * positions of its cities (the position's own city or its center's).
 */
class PositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('positions.view');
    }

    public function view(User $user, Position $position): bool
    {
        return $user->can('positions.view') && $this->isInActiveCity($user, $position);
    }

    public function create(User $user, ?int $cityId = null): bool
    {
        return $user->can('positions.create')
            && ($cityId === null || HrAccess::canAccessCity($user, $cityId));
    }

    public function update(User $user, Position $position): bool
    {
        return $user->can('positions.update') && $this->isInActiveCity($user, $position);
    }

    public function delete(User $user, Position $position): bool
    {
        return $user->can('positions.delete') && $this->isInActiveCity($user, $position);
    }

    /** Setting or clearing the critical tag: HR and the administrators only. */
    public function markCritical(User $user, Position $position): bool
    {
        return $user->can('positions.mark-critical') && $this->isInActiveCity($user, $position);
    }

    /**
     * The suggested-replacements panel of a critical position: HR of its city,
     * HR Syria, the general manager and the administrators. Never a direct
     * manager or the candidate, even if the permission were handed out by hand.
     */
    public function viewReplacements(User $user, Position $position): bool
    {
        return $position->is_critical
            && HrAccess::is($user, 'HR', 'general-manager', 'super-admin')
            && $user->can('positions.view-replacements')
            && $this->isInActiveCity($user, $position);
    }

    private function isInActiveCity(User $user, Position $position): bool
    {
        $cityIds = HrAccess::cityIds($user);

        return $cityIds === null
            || Position::query()->inCity($cityIds)->whereKey($position->getKey())->exists();
    }
}
