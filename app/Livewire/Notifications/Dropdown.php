<?php

namespace App\Livewire\Notifications;

use Livewire\Component;

class Dropdown extends Component
{
    public function getListeners()
    {
        return [
            'notification-created' => '$refresh',
            'center-created' => '$refresh',
            'center-closed' => '$refresh',
        ];
    }

    public function render()
    {
        return view('livewire.notifications.dropdown', [
            'notifications' => auth()->user()->unreadNotifications()->take(5)->get()
        ]);
    }

    public function markAsRead($id): void
    {
        auth()->user()->unreadNotifications()->where('id', $id)->update(['read_at' => now()]);
    }
}
