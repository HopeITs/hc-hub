<?php

namespace App\Livewire\Contract;

use App\Livewire\Forms\ContractForm;
use App\Models\{Employee, Position, ContractType, Department, JobLevel};
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class ContractCreate extends Component
{
    public ContractForm $form;

    /** Wizard state */
    public int $step = 1;     // 1 = identity, 2 = terms, 3 = comp
    public int $maxStep = 1;  // highest unlocked step

    /** Map step → validation method on the ContractForm */
    protected array $validatorsByStep = [
        1 => 'validateIdentity',
        2 => 'validateTerms',
        3 => 'validatePay',
    ];

    public function mount(): void
    {
        $this->form->setDefaults();
    }

    /** Next: validate current step only; do not save */
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

    /** Prev: go back a step */
    public function prev(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    /** Optional: if you later want tabs clickable once unlocked */
    public function jumpTo(int $target): void
    {
        $target = max(1, min(3, $target));
        if ($target <= $this->maxStep) {
            $this->step = $target;
        }
    }

    /** Final submit (validate all, then save once) */
    public function submit()
    {
        $this->form->validateIdentity();
        $this->form->validateTerms();
        $this->form->validatePay();

        $this->form->save();
        LivewireAlert::success(__('general.success-message'));

        return redirect()->route('contracts.index');
    }

    // Keep old methods no-ops to avoid breakage if referenced elsewhere
    public function saveIdentity(): void {}
    public function saveTerms(): void {}
    public function savePay(): void {}
    public function save() { return $this->submit(); }

    public function render()
    {
        $locale = app()->getLocale();
        return view('livewire.contract.contract-create', [
            'employees'   => Employee::where('city_id',auth()->user()->profile->city_id)->select('id','name')->orderBy("name->$locale")->get(),
            'positions'   => Position::with([
                'center:id,name',
                'requiredPosition:id,title',
            ])
                ->orderBy('id')
                ->get(['id','center_id','position_id']),
            'types'       => ContractType::select('id','name')->orderBy("name->$locale")->get(),
            'departments' => Department::select('id','name')->orderBy('name')->get(),
            'levels'      => JobLevel::select('id','name')->orderBy('id')->get(),
            'programs'   => \App\Models\Program::all(),
        ]);
    }
}
