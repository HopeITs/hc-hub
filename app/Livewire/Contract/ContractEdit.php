<?php

namespace App\Livewire\Contract;

use App\Livewire\Forms\ContractForm;
use App\Models\{Contract, Employee, Position, ContractType};
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class ContractEdit extends Component
{
    public ContractForm $form;
    public Contract $contract;

    public int $step = 1;
    public int $maxStep = 1;

    /** Keep the same step validation map as create */
    protected array $validatorsByStep = [
        1 => 'validateIdentity',
        2 => 'validateTerms',
        3 => 'validatePay',
    ];

    public function mount(Contract $contract): void
    {
        $this->contract = $contract;

        // Same defaults + prefill as create
        $this->form->setDefaults();
        $this->form->setContract($contract);
    }

    /** Same wizard controls as create */
    public function next(): void
    {
        $method = $this->validatorsByStep[$this->step] ?? null;
        if ($method && method_exists($this->form, $method)) {
            $this->form->{$method}();
        }

        if ($this->step < 3) {
            $this->step++;
            $this->maxStep = max($this->maxStep, $this->step);
        }
    }

    public function prev(): void
    {
        if ($this->step > 1) $this->step--;
    }

    public function jumpTo(int $target): void
    {
        $target = max(1, min(3, $target));
        if ($target <= $this->maxStep) $this->step = $target;
    }

    public function submit()
    {
        $this->form->validateIdentity();
        $this->form->validateTerms();
        $this->form->validatePay();

        $this->form->update($this->contract);

        LivewireAlert::success(__('general.success-message'));
        return redirect()->route('contracts.index');
    }

    // Legacy no-ops (keep to avoid breakage)
    public function saveIdentity(): void {}
    public function saveTerms(): void {}
    public function savePay(): void {}
    public function save() { return $this->submit(); }

    public function render()
    {
        $locale = app()->getLocale();

        return view('livewire.contract.contract-edit', [
            // Match create: employees scoped by user's city
            'employees' => Employee::where('city_id', auth()->user()->profile->city_id)
                ->select('id','name')
                ->orderBy("name->$locale")
                ->get(),

            // Positions include relations so identity partial can show derived info
            'positions' => Position::with([
                'center:id,name,city_id',
                'program:id,name',
                'department:id,name',
                'jobLevel:id,name,grade',
            ])
                ->orderBy('id')
                ->get(['id','center_id','program_id','department_id','job_level_id']),

            'types' => ContractType::select('id','name')->orderBy("name->$locale")->get(),
        ]);
    }
}
