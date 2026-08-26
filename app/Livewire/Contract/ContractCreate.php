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
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Locked;
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

    /**
     * The contract being renewed, when this page was opened as a renewal.
     * Locked: it decides the term anchor and the heading, and mount() is the
     * only place allowed to establish it (after the renew gate has passed).
     */
    #[Locked]
    public ?Contract $renewalSource = null;

    protected array $validatorsByStep = [
        1 => 'validateIdentity',
        2 => 'validateTerms',
        3 => 'validatePay',
    ];

    // ────────────────────────────────────────────────
    // Lifecycle
    // ────────────────────────────────────────────────

    /**
     * Serves both `/contracts/create` and `/contracts/{contract}/renew`. With a
     * source contract the wizard opens fully prefilled from it; without one it
     * behaves exactly as it always has.
     */
    public function mount(?Contract $contract = null): void
    {
        $this->form->setDefaults();

        if ($contract?->exists) {
            $this->initializeRenewal($contract);
        }
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
        $this->form->updatedContractTypeId($value, $this->contractTermAnchor());
    }

    public function updatedFormPositionId(mixed $value): void
    {
        $this->resetErrorBag('form.position_id');

        // Renewing onto the position the source contract already holds does not
        // consume a new seat, so the inline capacity warning must not fire.
        if (Contract::isRenewalOfPosition($this->form->renewal_source_id, (int) $value)) {
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
        // A renewal may sit open for a while. Re-read the source and re-check
        // the gate, so a contract terminated or moved between opening the
        // wizard and saving it cannot still be carried forward.
        if ($this->renewalSource) {
            $source = $this->renewalSource->fresh();

            // Deleted out from under the wizard: there is nothing left to renew,
            // and re-checking the gate against the stale copy would be a lie.
            abort_if($source === null, 403);

            Gate::authorize('renew', $source);
        }

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

    protected function locationOptionsEmployeeId(): ?int
    {
        // A renewal keeps its employee even when that employee has since been
        // deactivated, so the dropdown must still be able to show them.
        return $this->renewalSource?->employee_id;
    }

    protected function contractTermAnchor(): ?string
    {
        return $this->isRenewal() ? $this->form->start_date : null;
    }

    private function isRenewal(): bool
    {
        return $this->renewalSource !== null;
    }

    // ────────────────────────────────────────────────
    // Private — renewal
    // ────────────────────────────────────────────────

    private function initializeRenewal(Contract $contract): void
    {
        Gate::authorize('renew', $contract);

        // employee.media so the banner's avatar_url resolves from the loaded
        // relation rather than firing a media lookup of its own.
        $this->renewalSource = $contract->loadMissing(['position', 'employee.media']);

        [$start, $end] = $this->renewalWindowFromRequest($contract);

        $this->form->fillFromRenewal($contract, $start, $end);
        $this->initLocationContext($contract);

        // Every field arrives prefilled, so all three steps are already valid
        // destinations rather than being unlocked one at a time.
        $this->maxStep = 3;

        // Open on Terms, not Identity. A renewal already knows its employee and
        // position, and step 1 is the expensive screen: rendering it queries the
        // city and contract-type lists, then Alpine fires a second round trip to
        // locationOptions() for every employee and position in the city and
        // builds Select2 over them. Skipping it makes "Renew with Edit" land in
        // one page load instead of three sequential requests. Previous still
        // goes back to Identity for the rarer case of changing the assignment.
        $this->step = 2;
    }

    /**
     * The dates chosen in the renew modal, passed through the query string.
     *
     * They come from the browser and are re-validated here; anything missing or
     * malformed falls back to the contract's own default renewal window rather
     * than failing the page.
     *
     * @return array{0: string, 1: string}
     */
    private function renewalWindowFromRequest(Contract $contract): array
    {
        $requested = validator(request()->only(['start', 'end']), [
            'start' => ['required', 'date_format:Y-m-d'],
            'end' => ['required', 'date_format:Y-m-d', 'after_or_equal:start'],
        ]);

        if ($requested->fails()) {
            return $contract->renewalWindow();
        }

        $validated = $requested->validated();

        return [$validated['start'], $validated['end']];
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
