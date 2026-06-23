<?php

namespace App\Livewire\Contract;

use App\Livewire\Forms\ContractForm;
use App\Livewire\Forms\LeaveBalanceForm;
use App\Models\Center;
use App\Models\City;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Program;
use App\Models\TerminationReason;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class ContractEdit extends Component
{
    public ContractForm $form;
    public LeaveBalanceForm $balanceForm;
    public Contract $contract;

    public int $step = 1;
    public int $maxStep = 1;

    public ?int $city_id = null;
    public ?int $program_id = null;
    public ?int $center_id = null;

    protected array $validatorsByStep = [
        1 => 'validateIdentity',
        2 => 'validateTerms',
        3 => 'validatePay',
    ];

    // ────────────────────────────────────────────────
    // Lifecycle
    // ────────────────────────────────────────────────

    public function mount(Contract $contract): void
    {
        $this->contract = $contract->loadMissing('position.center');

        $this->form->setDefaults();
        $this->form->setContract($contract);

        $this->initLocationContext();
    }

    // ────────────────────────────────────────────────
    // Wired property hooks
    // ────────────────────────────────────────────────

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

    public function updatedFormPositionId(mixed $value): void
    {
        $this->resetErrorBag('form.position_id');

        // Skip capacity check when the position is unchanged
        if ($this->contract->position_id === (int) $value) {
            return;
        }

        $position = Position::find($value);

        if ($position && ! $position->canAssign()) {
            $this->addError('form.position_id', __('This position exceeded the number of max employees.'));
        }
    }

    // ────────────────────────────────────────────────
    // Step navigation
    // ────────────────────────────────────────────────

    public function next(): void
    {
        $validator = $this->validatorsByStep[$this->step] ?? null;

        if ($validator && method_exists($this->form, $validator)) {
            $this->form->{$validator}();
        }

        if ($this->step < 3) {
            $this->step++;
            $this->maxStep = max($this->maxStep, $this->step);
        }
    }

    public function prev(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function jumpTo(int $target): void
    {
        $target = max(1, min(3, $target));

        if ($target <= $this->maxStep) {
            $this->step = $target;
        }
    }

    // ────────────────────────────────────────────────
    // Submission
    // ────────────────────────────────────────────────

    public function submit(): mixed
    {
        $this->form->validateIdentity();
        $this->form->validateTerms();
        $this->form->validatePay();

        if (! $this->validateCityConsistency()) {
            return null;
        }

        $this->form->update($this->contract);

        // All contract types receive annual leave balances.
        if ($this->contract->leaveBalances()->exists()) {
            $this->balanceForm->update($this->contract->id);
        } else {
            $this->balanceForm->store($this->contract->id);
        }

        LivewireAlert::success(__('general.success-message'));

        return redirect()->route('contracts.show', $this->contract->id);
    }

    /** Alias kept for view compatibility. */
    public function save(): mixed
    {
        return $this->submit();
    }

    // ────────────────────────────────────────────────
    // Rendering
    // ────────────────────────────────────────────────

    public function render(): \Illuminate\View\View
    {
        $locale  = app()->getLocale();
        $posCol  = "title_{$locale}";
        $typeCol = "name_{$locale}";
        $cityCol = "name_{$locale}";
        $empCol  = transColumn('first_name');

        $cities              = $this->getCities($cityCol);
        $employees           = $this->getEmployees($empCol);
        $programs            = $this->getPrograms();
        $centers             = $this->getCenters();
        $positions           = $this->getPositions($posCol);
        $types               = $this->getContractTypes($typeCol);
        $terminationReasons  = TerminationReason::all();

        return view('livewire.contract.contract-edit', compact(
            'cities',
            'employees',
            'programs',
            'centers',
            'positions',
            'types',
            'terminationReasons',
        ));
    }

    // ────────────────────────────────────────────────
    // Private — initialisation
    // ────────────────────────────────────────────────

    private function initLocationContext(): void
    {
        $position = $this->contract->position;

        $effectiveCityId = $position?->center_id
            ? Center::whereKey($position->center_id)->value('city_id')
            : $position?->city_id;

        $this->city_id    = $effectiveCityId ?? $this->contract->employee?->city_id;
        $this->program_id = $position?->program_id;
        $this->center_id  = $position?->center_id;
    }

    // ────────────────────────────────────────────────
    // Private — query helpers
    // ────────────────────────────────────────────────

    private function getCities(string $col): \Illuminate\Support\Collection
    {
        return City::select('id', $col)
            ->where('is_active', true)
            ->get()
            ->each(fn (City $city) => $city->name = $city->{$col});
    }

    private function getEmployees(string $orderCol): \Illuminate\Support\Collection
    {
        $user    = auth()->user()->load('profile');
        $role    = $user->myRole();
        [$roleType, $roleScope] = array_pad(explode('-', $role, 2), 2, null);

        if ($roleType !== 'HR') {
            $cityId = optional($user->profile)->city_id;
            abort_unless($cityId, 403, 'User has no linked city.');

            return $this->withCurrentEmployee(
                Employee::where('is_active', true)
                    ->where('city_id', $cityId)
                    ->orderBy($orderCol)
                    ->get()
            );
        }

        $query = Employee::where('is_active', true)->orderBy($orderCol);

        if ($roleScope !== 'SYR') {
            $query->whereHas('city', fn ($q) => $q->where('slug', $roleScope));
        }

        return $this->withCurrentEmployee($query->get());
    }

    /**
     * Guarantee the contract's current employee appears in the list even if
     * they no longer match the active filter (e.g. they were deactivated).
     */
    private function withCurrentEmployee(\Illuminate\Support\Collection $employees): \Illuminate\Support\Collection
    {
        $currentId = $this->contract->employee_id;

        if ($currentId && $employees->doesntContain('id', $currentId)) {
            $current = Employee::find($currentId);

            if ($current) {
                $employees = $employees->push($current)->unique('id')->values();
            }
        }

        return $employees;
    }

    private function getPrograms(): \Illuminate\Support\Collection
    {
        if (! $this->city_id) {
            return collect();
        }

        $programIds = Position::query()
            ->inCity($this->city_id)
            ->pluck('program_id')
            ->unique();

        return Program::whereIn('id', $programIds)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    private function getCenters(): \Illuminate\Support\Collection
    {
        if (! $this->city_id || ! $this->program_id) {
            return collect();
        }

        return Center::select('centers.id', 'centers.name')
            ->whereHas('positions', function ($q) {
                $q->where('program_id', $this->program_id)
                    ->inCity($this->city_id);
            })
            ->orderBy('centers.name')
            ->get();
    }

    private function getPositions(string $col): \Illuminate\Support\Collection
    {
        if (! $this->city_id || ! $this->program_id) {
            return collect();
        }

        $positions = Position::where('program_id', $this->program_id)
            ->inCity($this->city_id)
            ->when($this->center_id, fn ($q) => $q->where('center_id', $this->center_id))
            ->select('id', $col)
            ->orderBy($col)
            ->get()
            ->each(fn (Position $p) => $p->title = $p->{$col});

        // Ensure the currently-assigned position is always present in the list
        if ($this->form->position_id && $positions->doesntContain('id', $this->form->position_id)) {
            $current = Position::select('id', $col)->find($this->form->position_id);

            if ($current) {
                $current->title = $current->{$col};
                $positions->push($current);
            }
        }

        return $positions;
    }

    private function getContractTypes(string $col): \Illuminate\Support\Collection
    {
        return ContractType::select('id', $col)
            ->orderBy($col)
            ->get()
            ->each(fn (ContractType $t) => $t->name = $t->{$col});
    }

    // ────────────────────────────────────────────────
    // Private — business logic
    // ────────────────────────────────────────────────

    private function validateCityConsistency(): bool
    {
        if (! $this->city_id || ! $this->form->position_id) {
            return true;
        }

        $position = Position::select('id', 'program_id', 'center_id', 'city_id')
            ->find($this->form->position_id);

        if (! $position) {
            $this->addError('form.position_id', __('validation.exists', ['attribute' => 'position']));
            return false;
        }

        // Use the same rule as the dropdown (Position::scopeInCity): a position
        // belongs to the city if its own city_id matches OR its center is in it.
        $belongsToCity = Position::whereKey($position->id)
            ->inCity($this->city_id)
            ->exists();

        if (! $belongsToCity) {
            $this->addError('form.position_id', __('validation.custom.position_id.invalid'));
            return false;
        }

        if ($this->program_id && (int) $position->program_id !== (int) $this->program_id) {
            $this->addError('form.position_id', __('validation.custom.position_id.program_mismatch'));
            return false;
        }

        if ($this->center_id && (int) $position->center_id !== (int) $this->center_id) {
            $this->addError('form.position_id', __('validation.custom.position_id.center_mismatch'));
            return false;
        }

        return true;
    }
}
