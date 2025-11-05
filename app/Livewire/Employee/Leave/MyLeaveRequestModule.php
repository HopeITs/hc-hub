<?php

namespace App\Livewire\Employee\Leave;

use App\Enums\LeaveRequestStatus;
use App\Livewire\Forms\LeaveRequestForm;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\News;
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

    protected $listeners = ['openRequestLeaveEdit' => 'edit'];
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
        $role = $user->activeRole()?->name;
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
                $query->where('employee_id', $employee->id);
            }

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
        $this->showModal = true;
        $this->modalTitle = __('general.add-new');
    }

    public function edit(LeaveRequest $id): void
    {
        $this->form->setLeaveRequest($id);
        $this->modalTitle = __('general.edit');
        $this->showModal = true;
    }

    public function render()
    {
        $employee = auth()->user()->profile;
        $availableLeaves = auth()->user()->profile->availableLeaves();
        $allAvailableLeaves = $employee->allAvailableLeaves();
        $alternativeEmployees = Employee::where('city_id', $employee->city_id)->where('id', '!=', $employee->id)
            ->whereHas('contracts', function ($query) use ($employee) {
                $query->whereHas('position.department', function ($query) use ($employee) {
                    $query->where('id', $employee->department->id ?? null);
                });
            })->get();

        return view('livewire.employee.leave.index', compact('availableLeaves', 'allAvailableLeaves', 'alternativeEmployees'));
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

    public function save()
    {
        if (isset($this->form->id)) {
            $leaveRequest = LeaveRequest::find($this->form->id);
            $this->form->setLeaveRequest($leaveRequest);
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


    }
}
