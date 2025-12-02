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
        $this->count = auth()->user()->unreadNotifications()->count();
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
