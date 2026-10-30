<?php

namespace Modules\Hr\Notifications;

use App\Notifications\Concerns\RoutesToInbox;
use App\Notifications\InboxNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Hr\Models\Employee;

class NewEmployeeWelcome extends Notification implements InboxNotification, ShouldQueue
{
    use Queueable;
    use RoutesToInbox;

    public function __construct(public Employee $employee) {}

    public function inboxModule(): string
    {
        return 'employees';
    }

    public function toArray($notifiable): array
    {
        $emp = $this->employee;

        return [
            'title' => [
                'en' => "Welcome $emp->name_en!",
                'ar' => "مرحبًا بـ$emp->name_ar!",
            ],

            'body' => [
                'en' => "Let's welcome $emp->name_en to the team, we’re excited to have them join us!",
                'ar' => "أهلًا وسهلًا بـ$emp->name_ar في فريقنا!",
            ],
            'url' => route('employees.show', $this->employee->username),
        ];
    }
}
