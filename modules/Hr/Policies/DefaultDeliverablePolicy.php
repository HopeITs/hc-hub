<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Models\DefaultDeliverable;
use Modules\Hr\Support\Settings\HrAccess;

/**
 * Default deliverables bind a checklist item to the employee who hands it
 * out in a governorate, so the record's governorate has to be inside the
 * actor's scope (SaveDefaultDeliverable also checks the chosen employee
 * lives there).
 */
class DefaultDeliverablePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('checklists.manage');
    }

    public function view(User $user, DefaultDeliverable $deliverable): bool
    {
        return $user->can('checklists.manage') && $this->cityAllowed($user, (int) $deliverable->city_id);
    }

    public function create(User $user, ?int $cityId = null): bool
    {
        return $user->can('checklists.manage') && ($cityId === null || $this->cityAllowed($user, $cityId));
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
        $cityIds = HrAccess::for($user)->cityIds();

        return $cityIds === null || in_array($cityId, $cityIds, true);
    }
}
