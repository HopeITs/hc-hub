<?php

namespace App\Livewire\Notifications;

use Livewire\Component;
use Livewire\Attributes\On;

class Dropdown extends Component
{
    #[On('notification-read')]
    #[On('notification-created')]
    #[On('center-created')]
    #[On('center-closed')]
    public function refreshList()
    {
    }

    public function render()
    {
        $user = auth()->user();

        $unread = $user->unreadNotifications()
            ->latest()
            ->take(3)
            ->get();

        $read = $unread->count() < 3
            ? $user->readNotifications()->latest()->take(3 - $unread->count())->get()
            : collect();

        return view('livewire.notifications.dropdown', [
            'notifications' => $unread->merge($read),
        ]);
    }

    public function markAsRead($id): void
    {
        auth()->user()
            ->notifications()
            ->where('id', $id)
            ->update(['read_at' => now()]);

        $this->dispatch('notification-read');
        $this->dispatch('$refresh');
    }
}
