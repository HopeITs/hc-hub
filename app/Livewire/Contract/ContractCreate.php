<?php

namespace App\Livewire\Contract;

use App\Livewire\Contract\Concerns\ResolvesContractLocationOptions;
use App\Livewire\Forms\ContractForm;
use App\Livewire\Forms\LeaveBalanceForm;
use App\Models\Center;
use App\Models\Contract;
use App\Models\Position;
use App\Models\TerminationReason;
use App\Models\User;
use App\Notifications\NewEmployeeWelcome;
use Illuminate\View\View;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class ContractCreate extends Component
{
    use ResolvesContractLocationOptions;

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

        $position = Position::query()
            ->select('id', 'max_emp', 'exist_emp')
            ->find($value);

        if ($position && ! $position->canAssign()) {
            $this->addError('form.position_id', __('This position exceeded the number of max employees.'));
        }
    }

    /**
     * Synchronize the Alpine-owned cascade once, then validate it server-side.
     */
    public function advanceIdentity(array $selection): void
    {
        $this->applyIdentitySelection($selection);
        $this->form->validateIdentity();

        if (! $this->validateCityConsistency()) {
            return;
        }

        $this->step = 2;
        $this->maxStep = max($this->maxStep, $this->step);
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

        // The three form sections and the cross-city graph were validated
        // immediately above, so persist without running the same queries twice.
        $contract = $this->form->create();

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

    public function render(): View
    {
        $locale = app()->getLocale();
        $typeCol = "name_{$locale}";
        $cityCol = "name_{$locale}";

        return view('livewire.contract.contract-create', [
            'cities' => $this->step === 1 ? $this->getCities($cityCol) : collect(),
            'types' => $this->step === 1 ? $this->getContractTypes($typeCol) : collect(),
            'terminationReasons' => $this->step === 2
                ? TerminationReason::query()->select('id', "title_{$locale}")->get()
                : collect(),
        ]);
    }

    protected function locationOptionsPermission(): string
    {
        return 'contracts.create';
    }

    private function notifyIfFirstContract(Contract $contract): void
    {
        $employee = $contract->employee;

        if ($employee->allContracts()->count() !== 1) {
            return;
        }

        // Queued notifications to active colleagues in the employee's city.
        User::sendBroadcast(new NewEmployeeWelcome($employee), $employee->city_id);
    }
}
