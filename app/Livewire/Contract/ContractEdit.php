<?php

namespace App\Livewire\Contract;

use App\Livewire\Forms\ContractForm;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\Position;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\JobLevel;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class ContractEdit extends Component
{
    public ContractForm $form;

    public function mount(Contract $contract): void
    {
        $this->form->setContract($contract);
    }

    public function saveIdentity(): void
    {
        $this->form->saveIdentity();
        LivewireAlert::success(__('general.success-message'));
    }

    public function saveTerms(): void
    {
        $this->form->saveTerms();
        LivewireAlert::success(__('general.success-message'));
    }

    public function saveCompensation(): void
    {
        $this->form->saveCompensation();
        LivewireAlert::success(__('general.success-message'));
    }

    public function save()
    {
        $this->form->save();
        LivewireAlert::success(__('general.success-message'));

        return redirect()->route('contracts.index');
    }

    public function render()
    {
        $locale = app()->getLocale();

        return view('livewire.contract.contract-edit', [
            'employees'   => Employee::select('id', 'name')->orderBy("name->$locale")->get(),
            'positions'   => Position::with('center:id,name')->select('id', 'center_id')->get(),
            'types'       => ContractType::select('id', 'name')->orderBy("name->$locale")->get(),
            'departments' => Department::select('id', 'name')->orderBy('name')->get(),
            'levels'      => JobLevel::select('id', 'name')->orderBy('id')->get(),
        ]);
    }
}
