<?php

namespace App\Livewire\Contract;

use App\Livewire\Contract\Concerns\ResolvesContractLocationOptions;
use App\Livewire\Forms\ContractForm;
use App\Livewire\Forms\LeaveBalanceForm;
use App\Models\Center;
use App\Models\Contract;
use App\Models\Position;
use App\Models\TerminationReason;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class ContractEdit extends Component
{
    use ResolvesContractLocationOptions;

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
        Gate::authorize('update', $contract);

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
        Gate::authorize('update', $this->contract);

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
        Gate::authorize('update', $this->contract);

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

    public function render(): View
    {
        $locale = app()->getLocale();
        $typeCol = "name_{$locale}";
        $cityCol = "name_{$locale}";

        $cities = $this->step === 1 ? $this->getCities($cityCol) : collect();
        $types = $this->step === 1 ? $this->getContractTypes($typeCol) : collect();
        $terminationReasons = $this->step === 2
            ? TerminationReason::query()->select('id', "title_{$locale}")->get()
            : collect();

        return view('livewire.contract.contract-edit', compact(
            'cities',
            'types',
            'terminationReasons',
        ));
    }

    protected function locationOptionsPermission(): string
    {
        return 'contracts.update';
    }

    protected function locationOptionsEmployeeId(): ?int
    {
        Gate::authorize('update', $this->contract);

        return $this->contract->employee_id;
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

        $this->city_id = $effectiveCityId ?? $this->contract->employee?->city_id;
        $this->program_id = $position?->program_id;
        $this->center_id = $position?->center_id;
    }
}
