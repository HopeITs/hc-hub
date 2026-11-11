<?php

declare(strict_types=1);

namespace Modules\Tracking\Notifications\Recipients;

use App\Notifications\Recipients\WatcherRecipients;

/**
 * Results: watched by MEAL.
 */
final class ResultRecipients extends WatcherRecipients
{
    protected function roles(): array
    {
        return ['MEAL'];
    }
}
