<?php

namespace App\Livewire\Notifications;

use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        $notifications = auth()->user()->notifications()->latest()->get();

        return view('livewire.notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(string $id): void
    {
        $user = auth()->user();
        $notification = $user->unreadNotifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
            $this->dispatch('notification-read');
        }
    }
}
