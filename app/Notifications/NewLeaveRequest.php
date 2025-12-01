<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLeaveRequest extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public LeaveRequest $leaveRequest)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $leaveRequest = $this->leaveRequest;
        $employee = $leaveRequest->leaveBalance->contract->employee;

        $leaveType = $leaveRequest->leaveBalance->leaveType;
        return [
            'title' => [
                'en' => "New Leave Request",
                'ar' => "طلب إجازة جديد",
            ],

            'body' => [
                'en' => "$employee->name_en has submitted a $leaveType->name_en request",
                'ar' => " قام $employee->name_ar بتقديم طلب $leaveType->name_ar",
            ],

            'url'   => route('leaves.all'),
        ];
    }
}
