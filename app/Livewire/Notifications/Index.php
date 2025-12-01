<?php

namespace App\Livewire\Notifications;

use Livewire\Component;
use Livewire\Attributes\On;

class Index extends Component
{
    #[On('notification-read')]
    public function render()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(10);

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
            $this->dispatch('$refresh');
        }
    }

    public function markAllRead(): void
    {

        $user = auth()->user();
        $notifications = $user->unreadNotifications()->whereJsonDoesntContainKey('data->url')->get();

        if ($notifications->count() > 0) {
            foreach ($notifications as $notification) {
                $notification->markAsRead();

            }
            $this->dispatch('notification-read');
            $this->dispatch('$refresh');
        }
    }
}
