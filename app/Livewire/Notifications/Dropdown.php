<?php

namespace App\Livewire\Notifications;

use App\Livewire\Concerns\HandlesNotificationActions;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Dropdown extends Component
{
    use HandlesNotificationActions;

    #[On('notification-read')]
    #[On('notification-created')]
    #[On('notif-arrived')]
    #[On('center-created')]
    #[On('center-closed')]
    public function refreshList(): void {}

    public function render(): View
    {
        $user = auth()->user();

        $unread = $user->unreadNotifications()
            ->latest()
            ->take(3)
            ->get();

        $read = $unread->count() < 3
            ? $user->readNotifications()->latest()->take(4 - $unread->count())->get()
            : collect();

        $notifications = $unread->merge($read);

        return view('livewire.notifications.dropdown', [
            'notifications' => $notifications,
            'states' => $this->requestStates($notifications),
            'hasUnread' => $user->unreadNotifications()->exists(),
        ]);
    }
}
