<?php

namespace App\Livewire\Employee\Leave;

use App\Enums\LeaveRequestStatus;
use App\Livewire\Forms\LeaveRequestForm;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\ActionLeaveRequest;
use Blade;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class AllLeaveRequestModule extends Component
{
    public LeaveBalance $leaveBalance;

    public LeaveRequest $leaveRequest;

    public LeaveRequestForm $form;

    public $formModal = false;

    public $modalTitle = 'Modal title';

    protected $listeners = [
        'openRequestLeaveEdit' => 'edit',
        'openShowRequestLeave' => 'show',
        'openDecisionRequestLeave' => 'openDecision',
        'processSave' => 'processSave',
    ];

    public string $statusFilter = 'all';

    public $selectedLeave;

    public $showModal = false;

    public $decisionModal = false;

    public $decisionLeave;

    public array $counts = [];

    public $allAvailableLeaves = [];

    public $alternativeEmployees = [];

    public function mount()
    {
        $this->refreshCounts();
    }

    /**
     * Employees selectable in the dropdown, scoped to the HR user's city.
     * Computed per render (not stored as state) so the heavy list is never
     * serialized into the Livewire payload on every request.
     */
    protected function scopedEmployees(): Collection
    {
        $role = auth()->user()->myRole();

        if (! str_contains($role, 'HR-')) {
            return collect();
        }

        $cityCode = explode('-', $role)[1];

        return Employee::query()
            ->when($cityCode !== 'SYR', fn ($q) => $q->whereHas('city', fn ($c) => $c->where('slug', $cityCode)))
            ->orderBy(transColumn('first_name'))
            ->get();
    }

    public function show($id)
    {
        $this->selectedLeave = LeaveRequest::query()
            ->visibleTo(auth()->user())
            ->with(['leaveType', 'leaveBalance.contract.employee', 'alternativeEmployee'])
            ->findOrFail($id);

        $this->modalTitle = 'Leave Details';
        $this->showModal = true;
    }

    public function edit(LeaveRequest $id)
    {
        $this->authorizeVisibleLeaveRequest($id);

        $this->form->setLeaveRequest($id);

        $employee = $id->leaveBalance->contract->employee;

        // hydrate dependent lists
        $this->allAvailableLeaves = $this->leavesFor($employee);
        $this->alternativeEmployees = $this->alternativeEmployeesFor($employee);

        // hydrate form values
        $this->form->employee_id = $employee->id;
        $this->form->leave_balance_id = (int) $id->leave_balance_id;

        $this->modalTitle = __('general.edit');
        $this->formModal = true;
    }

    public function updatedFormEmployeeId($employeeId)
    {
        if (! $employeeId) {
            $this->allAvailableLeaves = [];
            $this->alternativeEmployees = [];
            $this->form->leave_balance_id = null;

            return;
        }

        $employee = Employee::with('city')->find($employeeId);

        $this->allAvailableLeaves = $this->leavesFor($employee);
        $this->alternativeEmployees = $this->alternativeEmployeesFor($employee);

        // Reset dependent dropdowns
        $this->form->leave_balance_id = null;
        $this->form->alternative_employee = null;
    }

    /**
     * Creating on behalf of an employee: the chosen balance must belong to an
     * employee this user could have picked in the dropdown.
     */
    protected function authorizeVisibleLeaveBalance($leaveBalanceId): void
    {
        // Null falls through to the form's "required" validation message.
        if (! $leaveBalanceId) {
            return;
        }

        $balance = LeaveBalance::with('contract')->find($leaveBalanceId);

        abort_unless(
            $balance !== null
            && $this->scopedEmployees()->contains('id', $balance->contract?->employee_id),
            403
        );
    }

    /**
     * Actions on this page receive arbitrary ids from the client; every one
     * must be re-checked against the caller's visibility scope (city for HR,
     * managed employees for managers).
     */
    protected function authorizeVisibleLeaveRequest(LeaveRequest $leaveRequest): void
    {
        abort_unless(
            LeaveRequest::query()
                ->visibleTo(auth()->user())
                ->whereKey($leaveRequest->getKey())
                ->exists(),
            403
        );
    }

    public function openDecision(LeaveRequest $id)
    {
        $this->authorizeVisibleLeaveRequest($id);

        $this->form->setDecisionLeave($id);
        $this->decisionLeave = $id->load(['leaveType', 'leaveBalance.contract.employee', 'alternativeEmployee']);

        $this->modalTitle = __('general.leave-decision');
        $this->decisionModal = true;
    }

    public function submitDecision()
    {
        $this->form->submitDecision();

        // Notify
        $leaveRequest = $this->form->decisionLeave->load('leaveBalance.contract.employee.city');
        $city = $leaveRequest->leaveBalance->contract->employee->city;
        $hr = User::whereHas('roles', function ($query) use ($city) {
            $query->where('name', 'HR-'.$city->slug);
        })->first();
        $employee = $leaveRequest->leaveBalance->contract->employee->account;
        isset($hr) ? $hr->notify(new ActionLeaveRequest($leaveRequest, 'hr')) : '';
        isset($employee) ? $employee->notify(new ActionLeaveRequest($leaveRequest)) : '';
        $this->dispatch('notification-created');

        $this->refreshCounts();
        $this->decisionModal = false;

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();

        $this->dispatch('$refresh');
    }

    public function setActiveTab(string $status)
    {
        $this->statusFilter = $status;
        $this->dispatch('$refresh');
    }

    public function refreshCounts(): void
    {
        $leaveRequests = LeaveRequest::query()->visibleTo(auth()->user());

        $this->counts = ['all' => (clone $leaveRequests)->count(),
            'pending' => (clone $leaveRequests)->where('status', LeaveRequestStatus::Pending)->count(),
            'accepted' => (clone $leaveRequests)->where('status', LeaveRequestStatus::Accepted)->count(),
            'rejected' => (clone $leaveRequests)->where('status', LeaveRequestStatus::Rejected)->count(), ];
    }

    public function create()
    {
        $this->form->reset();
        $this->formModal = true;
        $this->modalTitle = __('general.add-new');
    }

    public function render()
    {
        $employees = $this->scopedEmployees();
        $allAvailableLeaves = $this->allAvailableLeaves;
        $alternativeEmployees = $this->alternativeEmployees;

        return view('livewire.employee.leave.all', compact('employees', 'allAvailableLeaves', 'alternativeEmployees'));
    }

    public function confirmSave()
    {
        $html = $this->confirmationMessage();

        LivewireAlert::title(__('general.confirmation'))
            ->timer(null)
            ->question()
            ->html($html)
            ->withConfirmButton(__('general.save'))
            ->onConfirm('processSave')
            ->withCancelButton(__('general.cancel'))->show();

    }

    public function processSave()
    {
        if ($this->form->id) {
            abort_unless($this->form->leaveRequest !== null, 403);
            $this->authorizeVisibleLeaveRequest($this->form->leaveRequest);
            $this->form->update();
        } else {
            $this->authorizeVisibleLeaveBalance($this->form->leave_balance_id);
            $this->form->store();
        }

        $this->formModal = false;
        $this->form->reset();

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();

        $this->refreshCounts();
    }

    protected function confirmationMessage(): string
    {
        $leaveTypeName = LeaveBalance::find($this->form->leave_balance_id)
            ->leaveType
            ->name ?? 'N/A';

        $type = $this->form->type;

        $start = $this->resolveStart();
        $end = $this->resolveEnd();
        $duration = $this->resolveDuration();
        $date = $this->form->leave_date;
        $reason = $this->form->reason;

        return Blade::render(
            '<x-alerts.leave-request-confirmation
                :type="$type"
                :leave_type="$leaveTypeName"
                :start="$start"
                :end="$end"
                :duration="$duration"
                :reason="$reason"
                :date="$date"
            />',
            compact('type', 'leaveTypeName', 'start', 'end', 'duration', 'reason', 'date')
        );
    }

    public function confirmDelete($id)
    {

        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->confirmButtonColor(config('samples.styles.colors.primary'))
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('delete', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function delete($id)
    {
        LeaveRequest::query()
            ->visibleTo(auth()->user())
            ->findOrFail($id['id'])
            ->delete();
        $this->showModal = false;
        $this->refreshCounts();
        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();

    }

    /**
     * Leave balances selectable for an employee's latest active contract.
     * Single source of truth for the leave-type dropdown (with leaveType
     * eager-loaded for the option labels).
     */
    protected function leavesFor(?Employee $employee): Collection
    {
        return $employee?->latestActiveContract()
            ?->leaveBalances()
            ->with('leaveType')
            ->get() ?? collect();
    }

    /**
     * Colleagues in the same city and department who can cover for the employee.
     */
    protected function alternativeEmployeesFor(?Employee $employee): Collection
    {
        if (! $employee) {
            return collect();
        }

        return Employee::query()
            ->where('city_id', $employee->city_id)
            ->where('id', '!=', $employee->id)
            ->whereHas('contracts.position.department', fn ($q) => $q->where('id', $employee->department?->id))
            ->orderBy(transColumn('first_name'))
            ->get();
    }

    protected function resolveStart()
    {
        return $this->form->type === 'day'
            ? Carbon::parse($this->form->start_at)->format('d-m-Y')
            : $this->form->start_hour;
    }

    protected function resolveEnd()
    {
        return $this->form->type === 'day'
            ? Carbon::parse($this->form->end_at)->format('d-m-Y')
            : $this->form->end_hour;
    }

    protected function resolveDuration()
    {
        if ($this->form->type === 'day') {
            return Carbon::parse($this->form->start_at)
                ->diffInDays(Carbon::parse($this->form->end_at)) + 1;
        }

        return Carbon::parse($this->form->start_hour)
            ->floatDiffInHours(Carbon::parse($this->form->end_hour));
    }
}
