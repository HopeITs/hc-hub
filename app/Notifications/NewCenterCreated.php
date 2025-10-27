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
            'title' => __('We have a new center, established in :city.', ['city' => $this->center->city->name]),
            'body' => __('Center ":name" has been created.', ['name' => $this->center->name]),
            'url' => route('centers.index'),
        ];
    }
}
