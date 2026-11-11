<?php

namespace Modules\Hr\Support\Recruitment\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Hr\Models\Employee;

class NewEmployeeWelcome extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Employee $employee) {}

    public function via($notifiable): array
    {
        return [InboxChannel::class];
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
            'url' => route('hr.employees.show', ['employee' => $this->employee->username]),
        ];
    }
}
