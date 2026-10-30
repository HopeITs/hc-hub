<?php

namespace App\Notifications;

use App\Notifications\Concerns\RoutesToInbox;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TestNotification extends Notification implements InboxNotification
{
    use Queueable;
    use RoutesToInbox;

    public function inboxModule(): string
    {
        return 'hub';
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
