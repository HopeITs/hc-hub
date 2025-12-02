<?php

namespace App\Livewire\Notifications;

use Livewire\Component;
use Livewire\Attributes\On;

class Index extends Component
{
    #[On('notification-read')]
    public function render()
    {
        return view('livewire.notifications.index', [
            'notifications' => auth()->user()->notifications()->latest()->paginate(10),
        ]);
    }

    public function markAsRead(string $id): void
    {
        $notification = auth()->user()->unreadNotifications()->find($id);

        if ($notification) {
            $notification->markAsRead();

            $this->dispatch('notification-read');
            $this->dispatch('$refresh');
        }
    }

    public function markAllRead(): void
    {
        $notifications = auth()->user()
            ->unreadNotifications()
            ->whereJsonDoesntContainKey('data->url')
            ->get();

        foreach ($notifications as $note) {
            $note->markAsRead();
        }

        $this->dispatch('notification-read');
        $this->dispatch('$refresh');
    }
}
