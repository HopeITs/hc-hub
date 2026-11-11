<?php

declare(strict_types=1);

namespace Modules\Tracking\Notifications\Recipients;

use App\Notifications\Recipients\WatcherRecipients;

/**
 * Strategies: watched by Managers.
 */
final class StrategyRecipients extends WatcherRecipients
{
    protected function roles(): array
    {
        return ['Managers'];
    }
}
