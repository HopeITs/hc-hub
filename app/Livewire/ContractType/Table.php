<?php

namespace App\Livewire\ContractType;


use App\Models\ContractType;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination ;
    public function render()
    {

        $items = ContractType::paginate(10);

        return view('livewire.contract-type.table', [
            'items' => $items,
        ]);

    }
}
