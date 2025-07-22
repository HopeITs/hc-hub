<?php

namespace App\Livewire\Layouts;

use App\Livewire\BaseComponent;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Header extends BaseComponent
{
    public function render()
    {
        $user = Auth::user();
        return view('livewire.layouts.header', compact('user'));
    }
}
