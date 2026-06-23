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
use App\Models\User;
use App\Notifications\NewEmployeeWelcome;
use Illuminate\Support\Facades\Notification;
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

    public ?int $center_id = null;

    protected array $validatorsByStep = [
        1 => 'validateIdentity',
        2 => 'validateTerms',
        3 => 'validatePay',
    ];

    // ────────────────────────────────────────────────
    // Lifecycle
    // ────────────────────────────────────────────────

    public function mount(): void
    {
        $this->form->setDefaults();
    }

    // ────────────────────────────────────────────────
    // Wired property hooks
    // ────────────────────────────────────────────────

    public function updatedCityId(): void
    {
        // Changing the city invalidates every downstream selection.
        $this->reset(['form.employee_id', 'program_id', 'center_id', 'form.position_id']);
    }

    public function updatedProgramId(): void
    {
        // Changing the program invalidates center + position, but NOT employee.
        $this->reset(['center_id', 'form.position_id']);
    }

    public function updatedCenterId(): void
    {
        // Narrowing/clearing the center invalidates position only.
        $this->reset(['form.position_id']);
    }

    public function updatedFormContractTypeId(mixed $value): void
    {
        $this->form->updatedContractTypeId($value);
    }

    public function updatedFormPositionId(mixed $value): void
    {
        $this->resetErrorBag('form.position_id');

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

        $contract = $this->form->save();

        // All contract types receive annual leave balances.
        $this->balanceForm->store($contract->id);

        $this->notifyIfFirstContract($contract);

        $this->dispatch('notification-created');

        LivewireAlert::success(__('general.success-message'));

        return redirect()->route('contracts.show', $contract->id);
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
        $locale = app()->getLocale();
        $posCol = "title_{$locale}";
        $typeCol = "name_{$locale}";
        $cityCol = "name_{$locale}";
        $empCol = transColumn('first_name');

        return view('livewire.contract.contract-create', [
            'cities' => $this->getCities($cityCol),
            'employees' => $this->getEmployees($empCol),
            'programs' => $this->getPrograms(),
            'centers' => $this->getCenters(),
            'positions' => $this->getPositions($posCol),
            'types' => $this->getContractTypes($typeCol),
            'terminationReasons' => TerminationReason::all(),
        ]);
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

    /**
     * Employees selectable for the contract, scoped by the current user's role.
     *
     * - No city selected → empty (nothing to show yet).
     * - Non-HR roles     → pinned to the user's own city.
     * - HR-SYR           → every active employee (nationwide HR).
     * - Other HR-{scope}  → employees in the city matching the role scope slug.
     */
    private function getEmployees(string $orderCol): \Illuminate\Support\Collection
    {
        if (! $this->city_id) {
            return collect();
        }

        $user = auth()->user()->load('profile');
        [$roleType, $roleScope] = array_pad(explode('-', $user->myRole(), 2), 2, null);

        // Non-HR users are pinned to their own city.
        if ($roleType !== 'HR') {
            $cityId = optional($user->profile)->city_id;
            abort_unless($cityId, 403, 'User has no linked city.');

            return Employee::where('is_active', true)
                ->where('city_id', $cityId)
                ->orderBy($orderCol)
                ->get();
        }

        $query = Employee::where('is_active', true)->orderBy($orderCol);

        // HR-SYR sees every active employee; all other HR roles are city-scoped.
        if ($roleScope !== 'SYR') {
            $query->whereHas('city', fn ($q) => $q->where('slug', $roleScope));
        }


        return $query->get();
    }

    /**
     * Programs that have at least one position reachable from the selected city.
     *
     * - Global city → programs with positions whose city_id = 1 (direct) OR
     *                 whose center belongs to city 1.
     * - Other city  → same logic for that city.
     * - No city     → empty.
     */
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

    /**
     * Centers relevant to the selected city + program combination.
     *
     * Requires both city and program; returns centers that have at least one
     * position in the selected program whose effective city matches.
     */
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

    /**
     * Positions for the selected program + city, optionally narrowed by center.
     *
     * Requires both city and program to be selected before returning results.
     * When a center is also selected, only positions belonging to that center
     * are returned.
     */
    private function getPositions(string $col): \Illuminate\Support\Collection
    {
        if (! $this->city_id || ! $this->program_id) {
            return collect();
        }

        $positions = Position::where('program_id', $this->program_id)
            ->inCity($this->city_id)
            ->when($this->center_id, fn ($q) => $q->where('center_id', $this->center_id))
            ->select('id', $col, 'center_id', 'city_id')
            ->orderBy($col)
            ->get()
            ->each(fn (Position $p) => $p->title = $p->{$col});

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

    /**
     * Cross-checks that the chosen position actually belongs to the selected
     * city/program/center combination before persisting.
     */
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

    private function notifyIfFirstContract(Contract $contract): void
    {
        $employee = $contract->employee;

        if ($employee->allContracts()->count() !== 1) {
            return;
        }

        // Queued, batched broadcast to every user (database channel).
        Notification::send(User::all(), new NewEmployeeWelcome($employee));
    }
}
