<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ActionLeaveRequest extends Notification
{
    use Queueable;

    public function __construct(
        public LeaveRequest $leaveRequest,
        public string $recipientType = 'employee'// employee | hr
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $leaveRequest = $this->leaveRequest;
        $leaveType = $leaveRequest->leaveBalance->leaveType;
        $status = $leaveRequest->status;

        $isAccepted = $status === \App\Enums\LeaveRequestStatus::Accepted;



        if ($this->recipientType === 'employee') {
            return [
                'title' => [
                    'en' => $isAccepted ? 'Leave Approved' : 'Leave Rejected',
                    'ar' => $isAccepted ? 'تم قبول الإجازة' : 'تم رفض الإجازة',
                ],
                'body' => [
                    'en' => $isAccepted
                        ? "Your leave request has been approved: {$leaveType->name_en}"
                        : "Your leave request has been rejected: {$leaveType->name_en}",
                    'ar' => $isAccepted
                        ? "تم قبول إجازتك من نوع: {$leaveType->name_ar}"
                        : "تم رفض طلب الإجازة من نوع: {$leaveType->name_ar}",
                ],
                'url' => route('leaves.index'),
            ];
        }


        if ($this->recipientType === 'hr') {
            $employee = $leaveRequest->leaveBalance->contract->employee;
            return [
                'title' => [
                    'en' => $isAccepted
                        ? 'Leave Request Approved'
                        : 'Leave Request Rejected',

                    'ar' => $isAccepted
                        ? 'تم قبول طلب إجازة'
                        : 'تم رفض طلب إجازة',
                ],

                'body' => [
                    'en' => $isAccepted
                        ? "{$leaveType->name_en} leave has been approved for employee: {$employee->name_en}"
                        : "{$leaveType->name_en} leave has been rejected for employee: {$employee->name_en}",

                    'ar' => $isAccepted
                        ? "تم قبول إجازة ({$leaveType->name_ar}) للموظف: {$employee->name_ar}"
                        : "تم رفض إجازة ({$leaveType->name_ar}) للموظف: {$employee->name_ar}",
                ],

                'url' => route('leaves.all'),
            ];
        }

        // fallback (optional)
        return [];
    }
}
