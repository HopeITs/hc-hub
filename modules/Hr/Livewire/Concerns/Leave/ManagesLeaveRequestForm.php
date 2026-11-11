<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Leave;

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Leave\SaveLeaveRequest;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Support\Leave\LeaveAlternativeEmployees;
use Modules\Hr\Support\Leave\LeaveContext;
use Modules\Hr\Support\Leave\LeaveEligibility;
use Modules\Hr\Support\Leave\LeaveFormat;
use Modules\Hr\Support\Leave\LeaveQuote;
use Modules\Hr\Support\Leave\LeaveRequestDraft;
use Modules\Hr\Support\Leave\LeaveRequestPricing;
use Modules\Hr\Support\Leave\LeaveWindow;
use Modules\Hr\Support\Settings\HrAccess;

/**
 * The leave request dialog shared by My leaves, Team leaves and a request's
 * own page: new (for oneself, or by HR for an employee of its governorates)
 * and edit. Fields are scalars; the impact summary is priced live by
 * SaveLeaveRequest::preview() and every write goes through SaveLeaveRequest.
 */
trait ManagesLeaveRequestForm
{
    public bool $showRequestModal = false;

    #[Locked]
    public ?int $editingLeaveRequestId = null;

    /** Set by HR when filing for someone else; '' means the signed-in user. */
    public string $requestEmployeeId = '';

    public string $requestType = LeaveWindow::DAY;

    public string $leaveBalanceId = '';

    public string $leaveDate = '';

    public string $startHour = '';

    public string $endHour = '';

    public string $startAt = '';

    public string $endAt = '';

    public string $reason = '';

    public string $alternativeEmployee = '';

    public bool $isExceptional = false;

    #[Locked]
    public ?string $submissionToken = null;

    /** Form property => the snake_case field SaveLeaveRequest reports. */
    private const REQUEST_FIELDS = [
        'requestType' => 'type',
        'leaveBalanceId' => 'leave_balance_id',
        'leaveDate' => 'leave_date',
        'startHour' => 'start_hour',
        'endHour' => 'end_hour',
        'startAt' => 'start_at',
        'endAt' => 'end_at',
        'reason' => 'reason',
        'alternativeEmployee' => 'alternative_employee',
        'isExceptional' => 'is_exceptional',
        'requestEmployeeId' => 'employee_id',
    ];

    public function openRequestForm(?int $employeeId = null): void
    {
        $this->resetRequestForm();

        if ($employeeId !== null) {
            Gate::authorize('createForOthers', LeaveRequest::class);
            $this->requestEmployeeId = (string) $employeeId;
        } elseif (! $this->canRequestForSelf()) {
            if (! Gate::allows('createForOthers', LeaveRequest::class)) {
                Flux::toast(variant: 'danger', text: __('hr_leave.validation.no-employee'));

                return;
            }
        }

        $this->submissionToken = (string) Str::uuid();
        $this->startAt = today()->format('Y-m-d');
        $this->endAt = today()->format('Y-m-d');
        $this->showRequestModal = true;
    }

    public function openEditForm(int $leaveRequestId): void
    {
        $leaveRequest = LeaveContext::request($leaveRequestId);
        Gate::authorize('update', $leaveRequest);

        $this->resetRequestForm();
        $this->editingLeaveRequestId = $leaveRequest->id;
        $this->requestEmployeeId = (string) $leaveRequest->leaveBalance->contract->employee_id;

        foreach (LeaveRequestDraft::fieldsOf($leaveRequest) as $field => $value) {
            $property = array_search($field, self::REQUEST_FIELDS, true);

            if (is_string($property)) {
                $this->{$property} = is_bool($value) ? $value : (string) $value;
            }
        }

        $this->showRequestModal = true;
    }

    public function updatedRequestEmployeeId(): void
    {
        $this->leaveBalanceId = '';
        $this->alternativeEmployee = '';
        unset($this->requestEmployee, $this->requestBalances, $this->alternativeOptions);
    }

    public function saveRequest(): void
    {
        /** @var User $actor */
        $actor = Auth::user();
        $input = $this->requestInput();

        try {
            if ($this->editingLeaveRequestId !== null) {
                app(SaveLeaveRequest::class)->update($actor, $this->editingLeaveRequestId, $input);
                $message = __('hr_leave.ui.request_updated');
            } else {
                app(SaveLeaveRequest::class)->create($actor, $this->requestEmployeeId === '' ? null : (int) $this->requestEmployeeId, $input, $this->submissionToken);
                $message = __('hr_leave.ui.request_sent');
            }
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(
                collect($exception->errors())->mapWithKeys(fn (array $messages, string $key): array => [
                    (array_search($key, self::REQUEST_FIELDS, true) ?: 'reason') => $messages,
                ])->all(),
            );
        }

        $this->closeRequestForm();
        $this->afterRequestSaved();

        Flux::toast(variant: 'success', text: $message);
    }

    public function closeRequestForm(): void
    {
        $this->showRequestModal = false;
        $this->resetRequestForm();
    }

    /** The page refreshes what it shows after a save. */
    protected function afterRequestSaved(): void {}

    protected function canRequestForSelf(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->employee_id !== null && $user->can('leaves.create');
    }

    /** The employee the open form is about. */
    #[Computed]
    public function requestEmployee(): ?Employee
    {
        /** @var User|null $user */
        $user = Auth::user();
        $id = $user?->employee_id;

        if ($this->editingLeaveRequestId !== null) {
            // The edited request's own employee, never a client-written id.
            $id = LeaveRequest::query()->whereKey($this->editingLeaveRequestId)
                ->join('leave_balances', 'leave_balances.id', '=', 'leave_requests.leave_balance_id')
                ->join('contracts', 'contracts.id', '=', 'leave_balances.contract_id')
                ->value('contracts.employee_id');
        } elseif ($this->requestEmployeeId !== '' && (int) $this->requestEmployeeId !== $user?->employee_id) {
            // HR's pick, kept inside the employees it may file for.
            $id = array_key_exists((int) $this->requestEmployeeId, $this->requestEmployeeOptions) ? (int) $this->requestEmployeeId : null;
        }

        return $id === null ? null : LeaveRequestDraft::employeeQuery()->find($id);
    }

    /**
     * The balances offered in the form: the requestable ones, plus the
     * edited request's own (an edit that keeps its type is not asked again).
     *
     * @return list<array{id: int, label: string, remaining: string}>
     */
    #[Computed]
    public function requestBalances(): array
    {
        $employee = $this->requestEmployee;
        $contract = $employee === null ? null : LeaveContext::contractOf((int) $employee->id);

        if ($contract === null) {
            return [];
        }

        $eligibility = app(LeaveEligibility::class);
        $hoursPerDay = app(LeaveRequestPricing::class)->hoursPerDayFor($contract);
        $keep = $this->editingLeaveRequestId === null ? null : (int) $this->leaveBalanceId;

        return $contract->leaveBalances
            ->filter(fn (LeaveBalance $balance) => (int) $balance->id === $keep || $eligibility->reasonAgainst($employee, $balance, $contract->leaveBalances) === null)
            ->sortBy('type_id')
            ->map(fn (LeaveBalance $balance) => [
                'id' => (int) $balance->id,
                'label' => (string) $balance->leaveType?->name,
                'remaining' => LeaveFormat::hours($eligibility->remainingHours($balance), $hoursPerDay),
            ])
            ->values()
            ->all();
    }

    /** @return array<int, string> id => name */
    #[Computed]
    public function alternativeOptions(): array
    {
        $employee = $this->requestEmployee;

        return $employee === null ? [] : app(LeaveAlternativeEmployees::class)->for($employee)
            ->mapWithKeys(fn (Employee $candidate) => [(int) $candidate->id => $candidate->name])
            ->all();
    }

    /** Employees HR may file a request for (its governorates, active). @return array<int, string> */
    #[Computed]
    public function requestEmployeeOptions(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null || ! Gate::allows('createForOthers', LeaveRequest::class)) {
            return [];
        }

        return HrAccess::for($user)->scopeToCities(LeaveRequestDraft::employeeQuery())
            ->where('is_active', true)
            ->whereHas('contracts', fn ($query) => $query->activeNow())
            ->orderBy(app()->getLocale() === 'ar' ? 'first_name_ar' : 'first_name_en')
            ->get()
            ->mapWithKeys(fn (Employee $employee) => [(int) $employee->id => $employee->name])
            ->all();
    }

    /** The live impact summary, or null while the form is partial. */
    #[Computed]
    public function requestPreview(): ?LeaveQuote
    {
        $employee = $this->requestEmployee;

        if (! $this->showRequestModal || $employee === null) {
            return null;
        }

        $editing = $this->editingLeaveRequestId === null ? null : LeaveRequest::query()->find($this->editingLeaveRequestId);

        return app(SaveLeaveRequest::class)->preview($employee, $this->requestInput(), $editing);
    }

    public function requestTouchesDecember(): bool
    {
        return LeaveRequestDraft::fromArray($this->requestInput())->touchesDecemberNow();
    }

    /**
     * @return array<string, mixed>
     */
    private function requestInput(): array
    {
        $input = [];

        foreach (self::REQUEST_FIELDS as $property => $field) {
            $input[$field] = $this->{$property};
        }

        return $input;
    }

    private function resetRequestForm(): void
    {
        $this->resetValidation();
        $this->reset(array_keys(self::REQUEST_FIELDS));
        $this->editingLeaveRequestId = null;
        $this->submissionToken = null;
        unset($this->requestEmployee, $this->requestBalances, $this->alternativeOptions, $this->requestPreview);
    }
}
