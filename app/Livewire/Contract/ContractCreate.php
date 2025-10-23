<?php

namespace App\Livewire\Contract;

use App\Enums\SalaryPer;
use App\Livewire\Forms\ContractForm;
use App\Livewire\Forms\LeaveBalanceForm;
use App\Models\{City, Center, Employee, Position, Program, ContractType};
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class ContractCreate extends Component
{
    public ContractForm $form;
    public LeaveBalanceForm $balanceForm;
    public int $step = 1;
    public int $maxStep = 1;

    public ?int $city_id = null;
    public ?int $program_id = null;
    public ?int $center_id = null; // Optional (nullable)

    protected array $validatorsByStep = [
        1 => 'validateIdentity',
        2 => 'validateTerms',
        3 => 'validatePay',
    ];

    public function mount(): void
    {
        $this->form->setDefaults();
    }

    public function updatedCityId(): void
    {
        $this->reset(['form.employee_id', 'program_id', 'center_id', 'form.position_id']);
    }

    public function updatedProgramId(): void
    {
        $this->reset(['center_id', 'form.position_id']);
    }

    public function updatedCenterId(): void
    {
        $this->reset(['form.position_id']);
    }

    public function updatedFormPositionId($value): void
    {
        $this->resetErrorBag('form.position_id');

        $pos = \App\Models\Position::find($value);

        if ($pos && ! $pos->canAssign()) {
            $this->addError('form.position_id', __('This position exceeded the number of max employees.'));
        }
    }


    public function next(): void
    {
        if (method_exists($this->form, $this->validatorsByStep[$this->step] ?? '')) {
            $this->form->{$this->validatorsByStep[$this->step]}();
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

        if (!$this->validateCityConsistency()) return;

        $contract = $this->form->save();

        if($contract->salary_per->value === SalaryPer::Month){
            $this->balanceForm->store($contract->id);
        }

        LivewireAlert::success(__('general.success-message'));

        return redirect()->route('contracts.show', ['contract' => $contract->id]);
    }

    private function validateCityConsistency(): bool
    {
        if (!$this->city_id) return true;


        // Position chain validation
        if ($this->form->position_id) {
            $pos = Position::select('id', 'program_id', 'center_id')->find($this->form->position_id);
            if (!$pos) {
                $this->addError('form.position_id', __('validation.exists', ['attribute' => 'position']));
                return false;
            }

            $centerCityId = $pos->center_id
                ? Center::where('id', $pos->center_id)->value('city_id')
                : $this->city_id; // If no center, assume belongs to current city

            if ($centerCityId != $this->city_id) {
                $this->addError('form.position_id', __('validation.custom.position_id.invalid'));
                return false;
            }

            if ($this->program_id && (int)$pos->program_id !== (int)$this->program_id) {
                $this->addError('form.position_id', __('validation.custom.position_id.program_mismatch'));
                return false;
            }

            if ($this->center_id && $pos->center_id && (int)$pos->center_id !== (int)$this->center_id) {
                $this->addError('form.position_id', __('validation.custom.position_id.center_mismatch'));
                return false;
            }
        }

        return true;
    }

    public function saveIdentity(): void {}
    public function saveTerms(): void {}
    public function savePay(): void {}
    public function save() { return $this->submit(); }


    public function render()
    {
        $locale = app()->getLocale();
        $cityCol = "name_{$locale}";
        $posCol = "title_{$locale}";
        $typeCol = "name_{$locale}";

        $cities = City::select('id', $cityCol)->where('is_active', 1)
            ->orderBy($cityCol)
            ->get()
            ->map(fn($c) => tap($c, fn() => $c->name = $c->{$cityCol}));

        $user = auth()->user()->load('profile');
        $role = $user->activeRole();

        $roleArr = explode('-', $role->name);
        if ($roleArr[0] === 'HR') {
            if ($roleArr[1] === 'SYR') {
                $employees = Employee::all();
            }
            else
            {
                $citySlug = $roleArr[1];
                $employees = Employee::whereHas('city', function ($query) use ($citySlug) {
                    $query->where('slug', $citySlug);
                })->get();

            }
        }
        else{
            $cityId = optional($user->profile)->city_id;
            if (!$cityId) {
                abort(403, 'User has no linked employee or city.');
            }
            $employees = Employee::where('city_id', $cityId)->get();
        }


        $programs = $this->city_id
            ? Program::whereIn('id', Position::query()
                ->where(function ($q) {
                    $q->whereHas('center', fn($c) => $c->where('city_id', $this->city_id))
                        ->orWhereNull('center_id');
                })
                ->pluck('program_id')
                ->unique()
            )->select('id', 'name')
                ->orderBy('name')
                ->get()
            : collect();

        $centers = ($this->city_id)
            ? Center::where('city_id', $this->city_id)
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
            : collect();

        $positions = ($this->city_id && $this->program_id)
            ? Position::where([
                ['program_id', '=', $this->program_id],
                ['city_id', '=', $this->city_id],
            ])
                ->select('id', $posCol)
                ->orderBy($posCol)
                ->get()
                ->map(fn($p) => tap($p, fn() => $p->title = $p->{$posCol}))
            : collect();

        $types = ContractType::select('id', $typeCol)
            ->orderBy($typeCol)
            ->get()
            ->map(fn($t) => tap($t, fn() => $t->name = $t->{$typeCol}));

        return view('livewire.contract.contract-create', [
            'cities'     => $cities,
            'employees'  => $employees,
            'programs'   => $programs,
            'centers'    => $centers,
            'positions'  => $positions,
            'types'      => $types,
        ]);
    }
}
