<?php

declare(strict_types=1);

namespace Modules\Tracking\Notifications\Recipients;

use App\Notifications\Recipients\WatcherRecipients;

/**
 * Accounts: watched by Financial.
 */
final class AccountRecipients extends WatcherRecipients
{
    protected function roles(): array
    {
        return ['Financial'];
    }
}
