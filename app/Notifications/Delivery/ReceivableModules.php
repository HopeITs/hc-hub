<?php

declare(strict_types=1);

namespace App\Notifications\Delivery;

use App\Models\Permission;
use Modules\Core\Models\User;

/**
 * The notification modules a person can receive, and so may mute or route
 * to a channel: an open module (config('notifications.open_modules')) always,
 * any other only with its view permission
 * (config('notifications.view_permissions'), else "{module}.view"), and only
 * when that permission exists. The same rule RecipientFilter applies when
 * sending.
 */
final class ReceivableModules
{
    /**
     * In config order.
     *
     * @return list<string>
     */
    public function for(User $user): array
    {
        return array_values(array_filter(
            array_keys((array) config('notifications.modules')),
            fn (string $module): bool => $this->receives($user, $module),
        ));
    }

    public function receives(User $user, string $module): bool
    {
        if (! array_key_exists($module, (array) config('notifications.modules'))) {
            return false;
        }

        if (in_array($module, (array) config('notifications.open_modules', []), true)) {
            return true;
        }

        $permission = (string) config("notifications.view_permissions.{$module}", "{$module}.view");

        return Permission::query()->where('name', $permission)->exists() && $user->can($permission);
    }
}
