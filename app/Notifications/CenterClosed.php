<?php

namespace App\Notifications;

use App\Models\Center;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CenterClosed extends Notification
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
            'title' => __('Center ":name" sadly has been closed.', ['name' => $this->center->name]),
            'body' => __('The center located in :city is now inactive.', ['city' => $this->center->city->name]),
            'url' => route('centers.index'),
        ];
    }
}
