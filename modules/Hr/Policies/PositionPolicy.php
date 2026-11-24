<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
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
     * The suggested-replacements panel of a critical position: whoever holds
     * positions.view-replacements (HR of its city, HR Syria, the general manager
     * and the administrators). Never the position's direct manager or its own
     * holder, even if the permission were handed out by hand.
     */
    public function viewReplacements(User $user, Position $position): bool
    {
        return $position->is_critical
            && $user->can('positions.view-replacements')
            && ! $this->managesOrHolds($user, $position)
            && $this->isInActiveCity($user, $position);
    }

    /** Whether the user sits on the position itself or on the one it reports to. */
    private function managesOrHolds(User $user, Position $position): bool
    {
        return $user->employee_id !== null && Contract::query()
            ->activeNow()
            ->where('employee_id', $user->employee_id)
            ->whereIn('position_id', array_filter([$position->getKey(), $position->direct_manager_id]))
            ->exists();
    }

    private function isInActiveCity(User $user, Position $position): bool
    {
        $cityIds = HrAccess::cityIds($user);

        return $cityIds === null
            || Position::query()->inCity($cityIds)->whereKey($position->getKey())->exists();
    }
}
