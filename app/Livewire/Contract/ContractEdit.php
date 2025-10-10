<?php

namespace App\Livewire\Contract;

use App\Livewire\Forms\ContractForm;
use App\Models\{Contract, City, Center, Employee, Position, Program, ContractType};
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class ContractEdit extends Component
{
    public ContractForm $form;
    public Contract $contract;

    public int $step = 1;
    public int $maxStep = 1;

    // Cascading filters
    public ?int $city_id = null;
    public ?int $program_id = null;
    public ?int $center_id = null;

    protected array $validatorsByStep = [
        1 => 'validateIdentity',
        2 => 'validateTerms',
        3 => 'validatePay',
    ];

    public function mount(Contract $contract): void
    {
        $this->contract = $contract;

        // Prefill form with existing contract
        $this->form->setDefaults();
        $this->form->setContract($contract);

        // Initialize filter cascade based on contract’s relations
        $this->city_id = optional($contract->employee)->city_id;
        $this->program_id = optional($contract->position)->program_id;
        $this->center_id = optional($contract->position)->center_id;
    }

    // ── Reactive resets
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

    // ── Navigation
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

    // ── Submit and redirect to details page
    public function submit()
    {
        $this->form->validateIdentity();
        $this->form->validateTerms();
        $this->form->validatePay();

        if (!$this->validateCityConsistency()) {
            return;
        }

        // Update contract
        $this->form->update($this->contract);

        LivewireAlert::success(__('general.success-message'));

        // Redirect to contract details after saving
        return redirect()->route('contracts.show', ['contract' => $this->contract->id]);
    }

    // ── Data consistency validation
    private function validateCityConsistency(): bool
    {
        if (!$this->city_id) return true;

        // Validate employee-city match
        if ($this->form->employee_id) {
            $valid = Employee::where('id', $this->form->employee_id)
                ->where('city_id', $this->city_id)
                ->exists();

            if (!$valid) {
                $this->addError('form.employee_id', __('validation.custom.employee_id.invalid'));
                return false;
            }
        }

        // Validate position dependencies
        if ($this->form->position_id) {
            $pos = Position::select('id', 'program_id', 'center_id')->find($this->form->position_id);

            if (!$pos) {
                $this->addError('form.position_id', __('validation.exists', ['attribute' => 'position']));
                return false;
            }

            $centerCityId = Center::where('id', $pos->center_id)->value('city_id');
            if ($centerCityId != $this->city_id) {
                $this->addError('form.position_id', __('validation.custom.position_id.invalid'));
                return false;
            }

            if ($this->program_id && (int)$pos->program_id !== (int)$this->program_id) {
                $this->addError('form.position_id', __('validation.custom.position_id.program_mismatch'));
                return false;
            }

            if ($this->center_id && (int)$pos->center_id !== (int)$this->center_id) {
                $this->addError('form.position_id', __('validation.custom.position_id.center_mismatch'));
                return false;
            }
        }

        return true;
    }

    // ── Legacy stubs
    public function saveIdentity(): void {}
    public function saveTerms(): void {}
    public function savePay(): void {}
    public function save() { return $this->submit(); }

    // ── View data
    public function render()
    {
        $locale = app()->getLocale();
        $cityNameCol = "name_{$locale}";
        $positionTitleCol = "title_{$locale}";
        $typeNameCol = "name_{$locale}";

        // CITIES
        $cities = City::query()
            ->select('id', $cityNameCol)
            ->orderBy($cityNameCol)
            ->get()
            ->map(fn($c) => tap($c, fn() => $c->name = $c->{$cityNameCol}));

        // EMPLOYEES
        $employees = $this->city_id
            ? Employee::query()
                ->where('city_id', $this->city_id)
                ->select('id', $cityNameCol)
                ->orderBy($cityNameCol)
                ->get()
                ->map(fn($e) => tap($e, fn() => $e->name = $e->{$cityNameCol}))
            : collect();

        // PROGRAMS
        $programs = $this->city_id
            ? Program::query()
                ->whereIn('id', Position::query()
                    ->whereHas('center', fn($q) => $q->where('city_id', $this->city_id))
                    ->pluck('program_id')
                    ->unique()
                )
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
            : collect();

        // CENTERS
        $centers = ($this->city_id && $this->program_id)
            ? Center::query()
                ->where('city_id', $this->city_id)
                ->whereIn('id', Position::query()
                    ->where('program_id', $this->program_id)
                    ->pluck('center_id')
                    ->unique()
                )
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
            : collect();

        // POSITIONS
        $positions = ($this->city_id && $this->program_id && $this->center_id)
            ? Position::query()
                ->where('program_id', $this->program_id)
                ->where('center_id', $this->center_id)
                ->select('id', $positionTitleCol)
                ->orderBy($positionTitleCol)
                ->get()
                ->map(fn($p) => tap($p, fn() => $p->title = $p->{$positionTitleCol}))
            : collect();

        // CONTRACT TYPES
        $types = ContractType::query()
            ->select('id', $typeNameCol)
            ->orderBy($typeNameCol)
            ->get()
            ->map(fn($t) => tap($t, fn() => $t->name = $t->{$typeNameCol}));

        return view('livewire.contract.contract-edit', [
            'cities'     => $cities,
            'employees'  => $employees,
            'programs'   => $programs,
            'centers'    => $centers,
            'positions'  => $positions,
            'types'      => $types,
        ]);
    }
}
