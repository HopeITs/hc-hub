<?php

namespace App\Livewire\Notifications;

use Livewire\Component;
use Livewire\Attributes\On;

class Counter extends Component
{
    public int $count = 0;

    #[On('notification-read')]
    #[On('notification-created')]
    #[On('center-created')]
    #[On('center-closed')]
    public function updateCount(): void
    {
        $new = auth()->user()->unreadNotifications()->count();

        // A new notification landed since we last checked → tell the UI to react.
        if ($new > $this->count) {
            $this->dispatch('notif-arrived');
        }

        $this->count = $new;
    }

    public function mount(): void
    {
        $this->updateCount();
    }

    public function render()
    {
        return view('livewire.notifications.counter');
    }
}
