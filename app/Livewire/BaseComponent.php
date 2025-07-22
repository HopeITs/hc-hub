<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BaseComponent extends Component
{
    public function getUserProperty()
    {
        return Auth::user();
    }
}
