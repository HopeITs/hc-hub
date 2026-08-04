<?php

namespace App\Notifications;

use App\Models\Center;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CenterClosed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Center $center) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => [
                'en' => "Center \"{$this->center->name_en}\" has sadly been closed.",
                'ar' => "المركز \"{$this->center->name_ar}\" تم إغلاقه للأسف.",
            ],

            'body' => [
                'en' => "The center located in {$this->center->city->name_en} is now inactive.",
                'ar' => "المركز الموجود في {$this->center->city->name_ar} أصبح غير فعّال.",
            ],

        ];
    }
}
