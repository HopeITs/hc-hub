<?php

namespace App\Notifications;

use App\Models\Checklist;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ChecklistAssigned extends Notification
{
    use Queueable;

    protected string $type;
    protected Checklist $checklist;

    public function __construct(Checklist $checklist, string $type)
    {
        $this->checklist = $checklist;
        $this->type = $type;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $employee = $this->checklist->employee?->name;
        $item = $this->checklist->item?->name;

        if ($this->type === 'deliver') {
            $message = "Please deliver {$item} to {$employee}";
        } else {
            $message = "Please receive {$item} from {$employee}";
        }

        return [
            'message' => $message,
            'url' => route('checklist-items.pending-delivers'),
        ];
    }
}
