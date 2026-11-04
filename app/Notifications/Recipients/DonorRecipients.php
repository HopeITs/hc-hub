<?php

declare(strict_types=1);

namespace App\Notifications\Recipients;

use App\Models\Activity;

/**
 * Donors: watched by Financial and the Public Relations Manager. A donor
 * request goes to the General Manager, who approves it; the decision goes
 * back to the person who asked, even when their role cannot view donors
 * (D-57).
 */
final class DonorRecipients extends WatcherRecipients
{
    protected function roles(): array
    {
        return ['Financial', 'Public Relations Manager'];
    }

    public function watcherRoles(Activity $activity): array
    {
        return match ($activity->event) {
            'donor.requested' => ['General Manager'],
            'donor.approved', 'donor.declined' => [],
            default => $this->roles(),
        };
    }

    public function directUserIds(Activity $activity): array
    {
        return in_array($activity->event, ['donor.approved', 'donor.declined'], true)
            ? $this->idsFromProperty($activity, 'requested_by')
            : [];
    }

    public function permissionExemptUserIds(Activity $activity): array
    {
        return $this->directUserIds($activity);
    }
}
