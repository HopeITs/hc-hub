<?php

namespace App\Livewire\Notifications;

use Livewire\Component;

class Counter extends Component
{
    public int $count = 0;

    protected $listeners = ['notification-read' => 'updateCount'];

    public function mount(): void
    {
        $this->updateCount();
    }

    public function updateCount(): void
    {
        $this->count = auth()->user()->unreadNotifications()->count();
    }

    public function render()
    {
        return view('livewire.notifications.counter');
    }
}
