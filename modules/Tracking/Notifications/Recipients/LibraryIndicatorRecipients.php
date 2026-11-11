<?php

declare(strict_types=1);

namespace Modules\Tracking\Notifications\Recipients;

use App\Notifications\Recipients\WatcherRecipients;

/**
 * The Indicator Library: watched by MEAL, who own it.
 */
final class LibraryIndicatorRecipients extends WatcherRecipients
{
    protected function roles(): array
    {
        return ['MEAL'];
    }
}
