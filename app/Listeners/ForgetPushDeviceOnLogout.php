<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Http\Controllers\PushSubscriptionController;
use Illuminate\Auth\Events\Logout;
use Modules\Core\Models\User;

/**
 * Signing out of a browser stops its pushes: the next person on a shared
 * computer must not get the previous one's notifications. The browser is
 * found by the hchub_device cookie the subscribe endpoint sets; only that
 * user's rows for that device are removed.
 */
final class ForgetPushDeviceOnLogout
{
    public function handle(Logout $event): void
    {
        $deviceId = request()->cookie(PushSubscriptionController::DEVICE_COOKIE);

        if (! $event->user instanceof User || ! is_string($deviceId) || $deviceId === '' || strlen($deviceId) > 36) {
            return;
        }

        $event->user->pushSubscriptions()->where('device_id', $deviceId)->delete();
    }
}
