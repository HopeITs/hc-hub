<?php

namespace App\Livewire\Position;

use App\Models\Position;
use App\Traits\SortableTable;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class PositionsModule extends Component
{


    public function render()
    {

        return view('livewire.position.index');
    }






}
