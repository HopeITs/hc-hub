<?php

namespace App\Livewire\Employee\Leave;

use App\Enums\LeaveRequestStatus;
use App\Livewire\Forms\LeaveRequestForm;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\ActionLeaveRequest;
use Carbon\Carbon;
use Blade;
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
        'processSave' => 'processSave'
    ];
    public string $statusFilter = 'all';
    public $selectedLeave;
    public $showModal = false;
    public $decisionModal = false;
    public $decisionLeave;
    public array $counts = [];
    public $employees = [];
    public $allAvailableLeaves = [];
    public $alternativeEmployees = [];

    public function mount()
    {

        $this->refreshCounts();
        $user = auth()->user();
        $locale = app()->getLocale();
    $empCol = "name_{$locale}";
        if (str_contains($user->myRole(), 'HR-')) {
            $cityCode = explode('-', $user->myRole())[1];

            if ($cityCode === 'SYR') {
                $this->employees = Employee::with('contracts.leaveBalances.leaveType')->orderBy($empCol)->get();
            } else {
                $this->employees = Employee::whereHas('city', function ($query) use ($cityCode) {
                    $query->where('code', $cityCode);


                })->with('contracts.leaveBalances.leaveType')->orderBy($empCol)->get();
            }
        }

    }

    public function show($id)
    {
        $this->selectedLeave = LeaveRequest::with(['leaveType', 'leaveBalance.contract.employee', 'alternativeEmployee'])->findOrFail($id);

        $this->modalTitle = 'Leave Details';
        $this->showModal = true;
    }
    public function edit(LeaveRequest $id)
    {
        $locale = app()->getLocale();
    $empCol = "name_{$locale}";
        $this->form->setLeaveRequest($id);

        $employee = $id->leaveBalance->contract->employee;

        // hydrate dependent lists
        $this->allAvailableLeaves = $employee->latestActiveContract()?->leaveBalances()->with('leaveType')->get();
        $this->alternativeEmployees = Employee::query()
            ->where('city_id', $employee->city_id)
            ->where('id', '!=', $employee->id)
            ->whereHas('contracts.position.department', fn($q) => $q->where('id', $employee->department?->id))
            ->orderBy($empCol)
            ->get();
        // hydrate form values

        $this->form->employee_id = $employee->id;
        $this->form->leave_balance_id = (int) $id->leave_balance_id;

        $this->modalTitle = __('general.edit');
        $this->formModal = true;
    }


    public function updatedFormEmployeeId($employeeId)
    {
        $locale = app()->getLocale();
    $empCol = "name_{$locale}";
        if (!$employeeId) {
            $this->allAvailableLeaves = [];
            $this->alternativeEmployees = [];
            $this->form->leave_balance_id = null;
            return;
        }

        $employee = Employee::with(['contracts.leaveBalances.leaveType', 'city'])
            ->find($employeeId);

        // Available leaves
        $this->allAvailableLeaves = $employee->availableLeaves();

        // Alternative employees
        $this->alternativeEmployees = Employee::where('city_id', $employee->city_id)
            ->where('id', '!=', $employee->id)
            ->whereHas('contracts.position.department', function ($q) use ($employee) {
                $q->where('id', $employee->department?->id);
            })
            ->orderBy($empCol)
            ->get();

        // Reset dependent dropdowns
        $this->form->leave_balance_id = null;
        $this->form->alternative_employee = null;
    }


    public function openDecision(LeaveRequest $id)
    {
        $this->form->setDecisionLeave($id);
        $this->decisionLeave = $id->load(['leaveType', 'leaveBalance.contract.employee', 'alternativeEmployee']);

        $this->modalTitle = __('general.leave-decision');
        $this->decisionModal = true;
    }

    public function submitDecision()
    {
        $this->form->submitDecision();

        //Notify
        $leaveRequest = $this->form->decisionLeave->load('leaveBalance.contract.employee.city');
        $city = $leaveRequest->leaveBalance->contract->employee->city;
        $hr = User::whereHas('roles', function ($query) use ($city) {
            $query->where('name', 'HR-' . $city->slug);
        })->first();
        $employee = $leaveRequest->leaveBalance->contract->employee->account;
        isset($hr) ? $hr->notify(new ActionLeaveRequest($leaveRequest ,'hr')) : '';
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
        $user = auth()->user();
        $employee = $user->profile;
        $role = $user->myRole();
        // Collect all leave requests through the relationship chain:
        $leaveRequests = LeaveRequest::whereHas('leaveBalance.contract', function ($query) use ($role, $employee) {
            if ($role === 'HR-SYR') {
                return true;
            } elseif (str_starts_with($role, 'HR-')) {
                // Extract city code from role, e.g. HR-ALP → ALP
                $cityCode = substr($role, 3);
                $query->whereHas('employee', function ($q) use ($cityCode) {
                    $q->whereHas('city', function ($qq) use ($cityCode) {
                        $qq->where('slug', $cityCode);
                    });
                });

            } else {
                //is manager
                $query->whereHas('employee', function ($q) use ($employee) {
                    $q->whereHas('positions', function ($qq) use ($employee) {
                        $currentPositions = $employee->positions->modelKeys();
                        $qq->whereIn('direct_manager_id', $currentPositions)->orWhereIn('tech_manager_id', $currentPositions);
                    });
                });
            }

        });

        $this->counts = ['all' => (clone $leaveRequests)->count(),
            'pending' => (clone $leaveRequests)->where('status', LeaveRequestStatus::Pending)->count(),
            'accepted' => (clone $leaveRequests)->where('status', LeaveRequestStatus::Accepted)->count(),
            'rejected' => (clone $leaveRequests)->where('status', LeaveRequestStatus::Rejected)->count(),];
    }

    public function create()
    {
        $this->form->reset();
        $this->formModal = true;
        $this->modalTitle = __('general.add-new');
    }

    public function render()
    {
        // $locale = app()->getLocale();
        // $empCol = "name_{$locale}";
        // $employees = collect($this->employees)->sortBy($empCol)->values();
        $employees = $this->employees;
        $allAvailableLeaves = $this->allAvailableLeaves;
        $alternativeEmployees = $this->alternativeEmployees;
        // $alternativeEmployees = collect($this->alternativeEmployees)->sortBy($empCol)->values();


        return view('livewire.employee.leave.all', compact('employees', 'allAvailableLeaves', 'alternativeEmployees'));
    }

    public function getCountsProperty()
    {
        return [
            'all' => LeaveRequest::count(),
            'pending' => LeaveRequest::where('status', LeaveRequestStatus::Pending)->count(),
            'accepted' => LeaveRequest::where('status', LeaveRequestStatus::Accepted)->count(),
            'rejected' => LeaveRequest::where('status', LeaveRequestStatus::Rejected)->count(),
        ];
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
            ->withCancelButton(__('general.cancel')) ->show();

    }

    public function processSave()
    {
        if ($this->form->id) {
            $this->form->update();
        } else {
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

        LeaveRequest::find($id['id'])->delete();
        $this->showModal = false;
        $this->refreshCounts();
        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();

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
