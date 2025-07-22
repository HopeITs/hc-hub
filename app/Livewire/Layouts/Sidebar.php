<?php

namespace App\Livewire\Layouts;

use App\Livewire\BaseComponent;
use Illuminate\Support\Facades\Auth;


class Sidebar extends BaseComponent
{
    public function render()
    {
        $user = Auth::user();
        return view('livewire.layouts.sidebar',compact('user'));
    }
}
