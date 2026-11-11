<?php

declare(strict_types=1);

namespace Modules\Tracking\Notifications\Recipients;

use App\Notifications\Recipients\WatcherRecipients;

/**
 * Funding agreements and donor reports: watched by Financial and the Public Relations Manager.
 */
final class FundingAgreementRecipients extends WatcherRecipients
{
    protected function roles(): array
    {
        return ['Financial', 'Public Relations Manager'];
    }
}
