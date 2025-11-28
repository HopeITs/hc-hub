<?php

namespace App\Notifications;

use App\Models\Center;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewCenterCreated extends Notification
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
                'en' => "We have a new center, established in {$this->center->city->name_en}.",
                'ar' => "لقد تم إنشاء مركز جديد في {$this->center->city->name_ar}.",
            ],

            'body' => [
                'en' => "Center \"{$this->center->name}\" has been created.",
                'ar' => "تم إنشاء المركز \"{$this->center->name}\".",
            ],
            'url' => route('centers.index'),
        ];
    }
}
