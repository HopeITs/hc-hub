<?php

namespace App\Livewire\Notifications;

use App\Livewire\Concerns\HandlesNotificationActions;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Index extends Component
{
    use HandlesNotificationActions;

    #[On('notification-read')]
    public function refreshList(): void {}

    public function render(): View
    {
        return view('livewire.notifications.index', [
            'notifications' => auth()->user()->notifications()->latest()->paginate(10),
        ]);
    }
}
