<?php

namespace App\Livewire\Layout;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Sidebar extends Component
{
    public function render()
    {
        $user = Auth::user();
        return view('livewire.layout.sidebar',compact('user'));
    }
}
