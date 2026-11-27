<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PushSubscription;
use Modules\Core\Models\User;

/**
 * A push device belongs to one user: only they may rename, switch, limit to
 * urgent or remove it, whatever their role. The notification settings page
 * also loads every device through the user's own pushSubscriptions(), so
 * this is the second lock on the same door.
 */
class PushSubscriptionPolicy
{
    /**
     * Determine whether the user can rename the device or change its switches.
     */
    public function update(User $user, PushSubscription $subscription): bool
    {
        return $this->owns($user, $subscription);
    }

    /**
     * Determine whether the user can remove the device.
     */
    public function delete(User $user, PushSubscription $subscription): bool
    {
        return $this->owns($user, $subscription);
    }

    private function owns(User $user, PushSubscription $subscription): bool
    {
        return (int) $subscription->user_id === $user->id;
    }
}
