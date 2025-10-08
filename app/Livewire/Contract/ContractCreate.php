<?php

namespace App\Livewire\Contract;

use App\Livewire\Forms\ContractForm;
use App\Models\{Employee, Position, ContractType};
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class ContractCreate extends Component
{
    public ContractForm $form;

    public int $step = 1;
    public int $maxStep = 1;

    protected array $validatorsByStep = [
        1 => 'validateIdentity',
        2 => 'validateTerms',
        3 => 'validatePay',
    ];

    public function mount(): void
    {
        $this->form->setDefaults();
    }

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

        $this->form->save();
        LivewireAlert::success(__('general.success-message'));

        return redirect()->route('contracts.index');
    }

    public function saveIdentity(): void {}
    public function saveTerms(): void {}
    public function savePay(): void {}
    public function save() { return $this->submit(); }

    public function render()
    {
        $locale = app()->getLocale();

        return view('livewire.contract.contract-create', [
            'employees' => Employee::where('city_id', auth()->user()->profile->city_id)
                ->select('id',transColumn('name'))->orderBy(transColumn('name'))->get(),

            'positions' => Position::with([
                'center:id,name,city_id',
                'program:id,name',
                'department:id,name',
                'jobLevel:id,'.transColumn('name').',grade',
            ])
                ->orderBy(transColumn('title'))
                ->get(['id',transColumn('title'),'center_id','program_id','department_id','job_level_id']),

            'types' => ContractType::select('id',transColumn('name'))->orderBy(transColumn('name'))->get(),
        ]);
    }
}
