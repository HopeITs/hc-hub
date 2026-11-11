<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Checklists;

use App\Enums\NotificationKind;
use App\Enums\NotificationPriority;
use App\Notifications\AppNotification;
use App\Notifications\NotificationPayload;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Models\User;
use Modules\Hr\Models\Checklist;
use Modules\Hr\Models\ChecklistItem;
use Modules\Hr\Models\Employee;
use Throwable;

/**
 * Tells the person who must hand an item over, or take it back, that the
 * task is waiting for them (the old ChecklistAssigned notification). Sent as
 * a hub notification whose sentence lives in hr_checklists.notifications and
 * whose link opens the matching pending list.
 */
final class ChecklistNotifier
{
    public const string DELIVER = 'deliver';

    public const string RECEIVE = 'receive';

    /**
     * @param  self::DELIVER|self::RECEIVE  $type
     * @return bool whether someone was notified
     */
    public function assigned(Checklist $checklist, string $type): bool
    {
        $recipientEmployeeId = $type === self::DELIVER ? $checklist->delivered_id : $checklist->receiver_id;

        if ($recipientEmployeeId === null) {
            return false;
        }

        $recipient = User::query()->where('employee_id', $recipientEmployeeId)->first();

        if ($recipient === null) {
            return false;
        }

        $employee = Employee::query()
            ->whereKey($checklist->employee_id)
            ->first(['id', 'first_name_en', 'first_name_ar', 'last_name_en', 'last_name_ar']);

        $payload = new NotificationPayload(
            kind: NotificationKind::Action,
            module: 'checklists',
            priority: NotificationPriority::Important,
            key: 'hr_checklists.notifications.'.$type,
            params: [
                'item' => (string) ChecklistItem::query()->whereKey($checklist->item_id)->value('name'),
                'employee' => ['localized' => ['en' => (string) $employee?->name_en, 'ar' => (string) $employee?->name_ar]],
            ],
            subjectType: null,
            subjectId: null,
            actorId: Auth::id() === null ? null : (int) Auth::id(),
            link: ['route' => $type === self::DELIVER ? 'hr.checklists.pending-delivers' : 'hr.checklists.pending-returns'],
        );

        try {
            $recipient->notify(new AppNotification($payload));
        } catch (Throwable $exception) {
            // A failed notification must never undo the assignment it reports.
            report($exception);

            return false;
        }

        return true;
    }
}
