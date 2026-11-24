<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;

/**
 * Where a notification can reach someone: the in-app inbox, a computer's
 * push, a phone's or tablet's push, or email. The value is the column name
 * on notification_preferences and the key inside its per-module categories.
 */
enum NotificationChannel: string
{
    use HasTranslatedLabel;

    case InApp = 'in_app';
    case Desktop = 'desktop';
    case Mobile = 'mobile';
    case Email = 'email';
}
