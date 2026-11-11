<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Checklists;

use Modules\Core\Models\User;
use Modules\Hr\Models\Checklist;
use Throwable;

/**
 * The two sidebar badges of the checklists area: items waiting for this user
 * to hand over, and items waiting for them to take back. A badge must never
 * take the sidebar down, so a failed count reads as none and is reported.
 */
final class ChecklistCounts
{
    public static function pendingDeliversFor(User $user): int
    {
        return self::count($user, 'delivered_id', 'delivered_status');
    }

    public static function pendingReturnsFor(User $user): int
    {
        return self::count($user, 'receiver_id', 'returned_status');
    }

    private static function count(User $user, string $personColumn, string $statusColumn): int
    {
        if ($user->employee_id === null) {
            return 0;
        }

        try {
            return Checklist::query()
                ->where($personColumn, $user->employee_id)
                ->where($statusColumn, ChecklistWorkflow::PENDING)
                ->count();
        } catch (Throwable $exception) {
            report($exception);

            return 0;
        }
    }
}
