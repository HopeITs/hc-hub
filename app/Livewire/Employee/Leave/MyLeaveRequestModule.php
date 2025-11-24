<?php

namespace App\Livewire\Employee\Leave;

use App\Enums\LeaveRequestStatus;
use App\Livewire\Forms\LeaveRequestForm;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Blade;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class MyLeaveRequestModule extends Component
{
    public LeaveBalance $leaveBalance;
    public LeaveRequest $leaveRequest;
    public LeaveRequestForm $form;
    public $showModal = false;
    public $modalTitle = 'Modal title';

    protected $listeners = [
        'openRequestLeaveEdit' => 'edit',
        'processSave' => 'processSave'
    ];

    public string $statusFilter = 'all';
    public array $counts = [];

    public function mount()
    {
        $this->refreshCounts();
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

        $leaveRequests = LeaveRequest::whereHas('leaveBalance.contract', function ($query) use ( $employee) {
                $query->where('employee_id', $employee->id);
        });

        $this->counts = [
            'all' => (clone $leaveRequests)->count(),
            'pending' => (clone $leaveRequests)->where('status', LeaveRequestStatus::Pending)->count(),
            'accepted' => (clone $leaveRequests)->where('status', LeaveRequestStatus::Accepted)->count(),
            'rejected' => (clone $leaveRequests)->where('status', LeaveRequestStatus::Rejected)->count(),
        ];
    }

    public function create()
    {
        $this->form->reset();
        $this->modalTitle = __('general.add-new');
        $this->showModal = true;
    }

    public function edit(LeaveRequest $id)
    {

        $this->form->setLeaveRequest($id);


        $this->modalTitle = __('general.edit');
        $this->showModal = true;
    }

    public function render()
    {
        $employee = auth()->user()->profile;
        $availableLeaves = $employee->availableLeaves();
        $allAvailableLeaves = $employee->allAvailableLeaves();

        $alternativeEmployees = Employee::where('city_id', $employee->city_id)
            ->where('id', '!=', $employee->id)
            ->whereHas('contracts.position.department', fn($q) =>
            $q->where('id', $employee->department->id ?? null)
            )
            ->get();

        return view('livewire.employee.leave.index', compact(
            'availableLeaves', 'allAvailableLeaves', 'alternativeEmployees'
        ));
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

        $this->showModal = false;
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
