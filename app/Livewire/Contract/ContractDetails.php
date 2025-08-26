<?php

namespace App\Livewire\Contract;

use App\Models\Contract;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class ContractDetails extends Component
{
    use AuthorizesRequests;

    public Contract $contract;

    public string $displayView = 'annual';

    public array $crumbs = [];

    public function mount(Contract $contract): void
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

        $this->displayView = $contract->contractType->category ?: 'annual';

        $this->crumbs = [
            ['label' => trans_choice('general.employee_management', 2)],
            ['label' => trans_choice('general.contracts', 2)],
            ['label' => '#'.$contract->id],
        ];
    }

    protected function viewPartialPath(): string
    {
        // resources/views/livewire/contract/partials/{annual|trial|service}.blade.php
        return "livewire.contract.partials.{$this->displayView}";
    }

    public function render()
    {
        $locale = app()->getLocale();

        return view('livewire.contract.contract-details', [
            'contract'     => $this->contract,
            'locale'       => $locale,
            'displayView'  => $this->displayView,          // 'annual' | 'trial' | 'service'
            'partialPath'  => $this->viewPartialPath(),     // convenience for @includeIf
            'crumbs'       => $this->crumbs,
        ])->title(trans_choice('general.contracts', 1));
    }
}
