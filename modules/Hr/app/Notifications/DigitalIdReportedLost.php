<?php

namespace Modules\Hr\Notifications;

use App\Enums\NotificationPriority;
use App\Notifications\Concerns\RoutesToInbox;
use App\Notifications\InboxNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Hr\Models\Employee;

/**
 * A staff member reported their own digital-ID card lost. Sent to HR of the
 * employee's city, who disable or re-issue it from the profile page
 * (docs/ux/access-policy.md §9 A2D-Q5).
 *
 * Carries the name only: no card link, no phone, nothing a forwarded
 * notification could add to what HR already sees on the profile.
 */
class DigitalIdReportedLost extends Notification implements InboxNotification
{
    use Queueable;
    use RoutesToInbox;

    public function __construct(public Employee $employee) {}

    public function inboxModule(): string
    {
        return 'employees';
    }

    public function inboxPriority(object $notifiable): NotificationPriority
    {
        return NotificationPriority::Important;
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => [
                'en' => __('digital_id.notification.title', [], 'en'),
                'ar' => __('digital_id.notification.title', [], 'ar'),
            ],
            'body' => [
                'en' => __('digital_id.notification.body', ['name' => $this->employee->nameForLocale('en')], 'en'),
                'ar' => __('digital_id.notification.body', ['name' => $this->employee->nameForLocale('ar')], 'ar'),
            ],
            'url' => route('employees.show', $this->employee->username),
        ];
    }
}
