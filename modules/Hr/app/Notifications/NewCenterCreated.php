<?php

namespace Modules\Hr\Notifications;

use App\Models\Center;
use App\Notifications\Concerns\RoutesToInbox;
use App\Notifications\InboxNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewCenterCreated extends Notification implements InboxNotification, ShouldQueue
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
                'en' => "We have a new center, established in {$this->center->city->name_en}.",
                'ar' => "تم إنشاء مركز جديد في {$this->center->city->name_ar}.",
            ],

            'body' => [
                'en' => "Center \"{$this->center->name_en}\" has been created.",
                'ar' => "تم إنشاء المركز \"{$this->center->name_ar}\".",
            ],

        ];
    }
}
