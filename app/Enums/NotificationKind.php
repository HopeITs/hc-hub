<?php

namespace App\Enums;

/**
 * Where a notification came from: somebody's action, a scheduled alert, or
 * a digest. Stored in `notifications.kind`. Values are lower case.
 */
enum NotificationKind: string
{
    case Action = 'action';
    case Alert = 'alert';
    case Digest = 'digest';

    public function label(): string
    {
        return __('notifications.kinds.'.$this->value);
    }
}
