<?php

namespace App\Notifications;

use App\Enums\NotificationPriority;
use App\Models\User;
use App\Models\UserNotification;

/**
 * A user's unread, visible (not snoozed) notifications counted per module,
 * and how many of them are critical: one grouped query.
 *
 * Not cached (tracking caches it): HR code marks rows read in many places,
 * through the Notifiable helpers and mass updates, and a cache any of them
 * forgot to clear would leave the bell wrong for minutes.
 */
class UnreadCounts
{
    /**
     * @return array{total: int, critical: int, modules: array<string, int>}
     */
    public function for(User $user): array
    {
        $rows = UserNotification::query()
            ->forUser($user)
            ->whereNull('read_at')
            ->visible()
            ->toBase()
            ->selectRaw('module, priority, count(*) as aggregate')
            ->groupBy('module', 'priority')
            ->get();

        $counts = ['total' => 0, 'critical' => 0, 'modules' => []];

        foreach ($rows as $row) {
            $count = (int) $row->aggregate;
            $module = (string) ($row->module ?? 'hub');
            $counts['total'] += $count;
            $counts['modules'][$module] = ($counts['modules'][$module] ?? 0) + $count;

            if ($row->priority === NotificationPriority::Critical->value) {
                $counts['critical'] += $count;
            }
        }

        $order = array_flip(array_keys((array) config('notifications.inbox_modules')));
        uksort($counts['modules'], fn (string $a, string $b): int => [$order[$a] ?? PHP_INT_MAX, $a] <=> [$order[$b] ?? PHP_INT_MAX, $b]);

        return $counts;
    }
}
