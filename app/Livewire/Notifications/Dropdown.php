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
        $user = auth()->user();

        $unread = $user->unreadNotifications()
            ->latest()
            ->take(3)
            ->get();

        if ($unread->count() < 3) {
            $remaining = 3 - $unread->count();

            $read = $user->readNotifications()
                ->latest()
                ->take($remaining)
                ->get();
        } else {
            $read = collect();
        }

        $notifications = $unread->merge($read);

        return view('livewire.notifications.dropdown', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead($id): void
    {
        auth()->user()
            ->notifications()
            ->where('id', $id)
            ->update(['read_at' => now()]);
    }
}
