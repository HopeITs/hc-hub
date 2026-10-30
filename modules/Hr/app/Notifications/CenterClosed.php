<?php

namespace Modules\Hr\Notifications;

use App\Models\Center;
use App\Notifications\Concerns\RoutesToInbox;
use App\Notifications\InboxNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CenterClosed extends Notification implements InboxNotification, ShouldQueue
{
    use Queueable;
    use RoutesToInbox;

    public function __construct(public Center $center) {}

    public function inboxModule(): string
    {
        return 'centers';
    }

    /** Sent to everyone it concerns, not to one person: a mute of the module silences it. */
    public function isDirect(): bool
    {
        return false;
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => [
                'en' => "Center \"{$this->center->name_en}\" has sadly been closed.",
                'ar' => "تم إغلاق المركز \"{$this->center->name_ar}\".",
            ],

            'body' => [
                'en' => "The center located in {$this->center->city->name_en} is now inactive.",
                'ar' => "أصبح المركز في {$this->center->city->name_ar} غير فعّال.",
            ],

        ];
    }
}
