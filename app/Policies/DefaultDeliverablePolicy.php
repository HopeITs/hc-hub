<?php

namespace App\Policies;

use App\Models\DefaultDeliverable;
use App\Models\User;

/**
 * Default deliverables bind a checklist item to the employee who hands it out
 * in a given city, so both the record's city and the *selected employee's*
 * city have to be inside the actor's scope. Checking only the record allowed a
 * regional user to nominate an employee from another city as the deliverer.
 */
class DefaultDeliverablePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasActivePermission('checklists.manage');
    }

    public function view(User $user, DefaultDeliverable $deliverable): bool
    {
        return $user->hasActivePermission('checklists.manage')
            && $this->cityAllowed($user, (int) $deliverable->city_id);
    }

    public function create(User $user, ?int $cityId = null): bool
    {
        return $user->hasActivePermission('checklists.manage')
            && ($cityId === null || $this->cityAllowed($user, $cityId));
    }

    public function update(User $user, DefaultDeliverable $deliverable): bool
    {
        return $this->view($user, $deliverable);
    }

    public function delete(User $user, DefaultDeliverable $deliverable): bool
    {
        return $this->view($user, $deliverable);
    }

    private function cityAllowed(User $user, int $cityId): bool
    {
        return $user->canAccessCity($cityId);
    }
}
