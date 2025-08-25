<?php

namespace App\Livewire\Contract;

use App\Models\Contract;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ContractDetails extends Component
{
    use AuthorizesRequests;

    public Contract $contract;

    public function mount(Contract $contract)
    {
        $contract->load([
            'employee',
            'position.center',
            'contractType',
            'department',
            'jobLevel',
        ]);

        $this->authorize('view', $contract);

        $this->contract = $contract;
    }

    public function render()
    {
        $locale = app()->getLocale();

        return view('livewire.contract.contract-details', [
            'contract' => $this->contract,
            'locale'   => $locale,
        ])->title(trans_choice('general.contracts', 1));
    }
}
