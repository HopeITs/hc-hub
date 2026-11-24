<?php

declare(strict_types=1);

namespace App\Notifications\Delivery;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Core\Models\User;

/**
 * The email copy of one or more notifications (an hourly or daily digest
 * batches several). Items arrive already rendered in the reader's language
 * by OutboxDispatcher, which also sets this mail's locale, so the layout
 * reads right-to-left for Arabic readers. Sent with notifyNow() from the
 * dispatcher (already a job or a scheduled command), never queued again.
 */
final class NotificationEmail extends Notification
{
    /**
     * @param  list<array{module: string, title: string, body: string, url: string}>  $items
     */
    public function __construct(public array $items, public bool $test = false) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $count = count($this->items);
        $single = $count === 1 ? $this->items[0] : null;

        return (new MailMessage)
            ->subject($single === null
                ? __('notification_delivery.summary.title', ['count' => $count])
                : $single['title'].': '.$single['body'])
            ->markdown('mail.notification', [
                'items' => $this->items,
                'heading' => $single === null ? __('notification_delivery.summary.title', ['count' => $count]) : $single['title'],
                'actionLabel' => $single === null ? __('notification_delivery.email.open_all') : __('notification_delivery.email.open'),
                'actionUrl' => $single === null ? route('notifications.index') : $single['url'],
                'footer' => $this->test ? __('notification_delivery.email.test_footer') : __('notification_delivery.email.footer', ['app' => config('app.name')]),
            ]);
    }
}
