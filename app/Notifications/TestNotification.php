<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TestNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => [
                'en' => 'Test Notification',
                'ar' => 'إشعار تجريبي',
            ],
            'body' => [
                'en' => 'This is a test notification to check the badge and dropdown.',
                'ar' => 'هذا إشعار تجريبي للتحقق من عداد الإشعارات والقائمة المنسدلة.',
            ],
        ];
    }
}
