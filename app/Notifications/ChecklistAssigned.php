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
        $employee = $this->checklist->employee;
        $item = $this->checklist->item?->name;

        if ($this->type === 'deliver') {
            $titleEn = 'Deliver Item';
            $titleAr = 'تسليم عنصر';

            $bodyEn = "Please deliver {$item} to {$employee?->name_en}";
            $bodyAr = "يرجى تسليم {$item} إلى {$employee?->name_ar}";

            $url = route('checklist-items.pending-delivers');
        } else {
            $titleEn = 'Receive Item';
            $titleAr = 'استلام عنصر';

            $bodyEn = "Please receive {$item} from {$employee?->name_en}";
            $bodyAr = "يرجى استلام {$item} من {$employee?->name_ar}";

            $url = route('checklist-items.pending-returns');
        }

        return [
            'title' => [
                'en' => $titleEn,
                'ar' => $titleAr,
            ],

            'body' => [
                'en' => $bodyEn,
                'ar' => $bodyAr,
            ],
            'url' => $url,
        ];
    }
}
