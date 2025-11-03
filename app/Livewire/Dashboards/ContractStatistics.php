<?php

namespace App\Livewire\Dashboards;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class ContractStatistics extends Component
{
    public function render(): View
    {
        return view('livewire.dashboards.contract-statistics', []);
    }
}
