<?php

namespace App\Livewire\Dashboards;

use App\Models\{Employee, Contract};
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EmployeeDashboard extends Component
{
    public $employee;
    public $contract;
    public $positionTitle;
    public $departmentName;
    public $joinedOn;
    public $workEmail;

    public function mount(): void
    {
        $locale = app()->getLocale();
        $titleCol = $locale === 'ar' ? 'title_ar' : 'title_en';
        $deptCol  = $locale === 'ar' ? 'name_ar'  : 'name_en';

        $user = auth()->user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }

        // Get employee linked to the logged-in user via profile relation
        $this->employee = $user->profile()->with(['media', 'city'])->first();

        if ($this->employee) {
            // Get earliest contract
            $this->contract = Contract::with(['position.department'])
                ->where('employee_id', $this->employee->id)
                ->orderBy('start_date', 'asc')
                ->first();

            // Position and department (localized)
            $this->positionTitle  = $this->contract?->position?->$titleCol ?? '—';
            $this->departmentName = $this->contract?->position?->department?->$deptCol ?? '—';

            // Joined On date
            $this->joinedOn = $this->contract?->start_date
                ? date('d M Y', strtotime($this->contract->start_date))
                : '—';

            // Work email from the authenticated user
            $this->workEmail = $user->email ?? '—';
        } else {
            $this->positionTitle = $this->departmentName = $this->joinedOn = $this->workEmail = '—';
        }
    }

    public function render()
    {
        return view('livewire.dashboards.employee-dashboard', [
            'employee'       => $this->employee,
            'contract'       => $this->contract,
            'positionTitle'  => $this->positionTitle,
            'departmentName' => $this->departmentName,
            'joinedOn'       => $this->joinedOn,
            'workEmail'      => $this->workEmail,
        ]);
    }
}
