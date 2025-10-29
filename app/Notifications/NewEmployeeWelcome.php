<?php

namespace App\Notifications;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewEmployeeWelcome extends Notification
{
    use Queueable;

    public function __construct(public Employee $employee) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $name = $this->employee->name;

        return [
            'title' => "Welcome $name!",
            'body'  => "Let's Welcome $name to the team, We’re excited to have them join us!",
            'url'   => route('employees.edit', $this->employee->id),
        ];
    }
}
