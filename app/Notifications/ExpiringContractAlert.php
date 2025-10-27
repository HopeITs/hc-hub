<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExpiringContractAlert extends Notification
{
    use Queueable;

    public function __construct(public Contract $contract) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Contract Expiry Alert',
            'body'  => "{$this->contract->employee->name}'s contract expires in 7 days.",
            'url'   => route('contracts.show', $this->contract->id),
        ];
    }
}
