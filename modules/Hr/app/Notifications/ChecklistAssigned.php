<?php

namespace Modules\Hr\Notifications;

use App\Notifications\Concerns\RoutesToInbox;
use App\Notifications\InboxNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Hr\Models\Checklist;

class ChecklistAssigned extends Notification implements InboxNotification
{
    use Queueable;
    use RoutesToInbox;

    protected string $type;

    protected Checklist $checklist;

    public function __construct(Checklist $checklist, string $type)
    {
        $this->checklist = $checklist;
        $this->type = $type;
    }

    public function inboxModule(): string
    {
        return 'checklists';
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
