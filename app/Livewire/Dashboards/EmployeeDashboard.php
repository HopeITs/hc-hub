<?php

namespace App\Livewire\Dashboards;

use Carbon\Carbon;
use App\Models\{Employee, Contract};
use Livewire\Component;

class EmployeeDashboard extends Component
{
    public $employee;
    public $contract;
    public $positionTitle;
    public $departmentName;
    public $joinedOn;
    public $workEmail;

    /**
     * Today's birthdays
     */
    public function getTodayBirthdaysProperty()
    {
        $today = now()->timezone(config('app.timezone'));
        $day = $today->day;
        $month = $today->month;

        $locale = app()->getLocale();
        $titleCol = $locale === 'ar' ? 'title_ar' : 'title_en';
        $deptCol  = $locale === 'ar' ? 'name_ar' : 'name_en';

        return Employee::query()
            ->whereNotNull('dob')
            ->whereRaw('DAY(dob) = ?', [$day])
            ->whereRaw('MONTH(dob) = ?', [$month])
            ->with(['media', 'contracts.position.department'])
            ->get()
            ->map(function ($emp) use ($titleCol, $deptCol) {
                $contract = Contract::with(['position.department'])
                    ->where('employee_id', $emp->id)
                    ->orderByDesc('start_date')
                    ->first();

                $emp->positionTitle  = $contract?->position?->$titleCol ?? null;
                $emp->departmentName = $contract?->position?->department?->$deptCol ?? null;

                return $emp;
            });
    }

    public function getUpcomingBirthdaysProperty()
    {
        $today = now()->timezone(config('app.timezone'))->startOfDay();
        $start = $today->copy()->addDay();
        $end   = $today->copy()->addDays(7);

        $locale = app()->getLocale();
        $titleCol = $locale === 'ar' ? 'title_ar' : 'title_en';
        $deptCol  = $locale === 'ar' ? 'name_ar' : 'name_en';

        return Employee::query()
            ->whereNotNull('dob')
            ->get()
            ->filter(function ($emp) use ($start, $end) {
                $dob = Carbon::parse($emp->dob)->setYear(now()->year);

                if ($dob->lt($start)) {
                    $dob->addYear();
                }

                return $dob->isBetween($start, $end, true);
            })
            ->map(function ($emp) use ($titleCol, $deptCol) {
                $contract = Contract::with(['position.department'])
                    ->where('employee_id', $emp->id)
                    ->orderByDesc('start_date')
                    ->first();

                $emp->positionTitle  = $contract?->position?->$titleCol ?? null;
                $emp->departmentName = $contract?->position?->department?->$deptCol ?? null;

                $dob = Carbon::parse($emp->dob)->setYear(now()->year);
                if ($dob->lt(now())) {
                    $dob->addYear();
                }

                $daysLeft = (int) now()->diffInDays($dob, false);
                $emp->upcomingDob = $dob->format('d M');

                if ($daysLeft === 0) {
                    $emp->daysLeftText = 'Today 🎂';
                } elseif ($daysLeft === 1) {
                    $emp->daysLeftText = 'Tomorrow';
                } else {
                    $emp->daysLeftText = "In {$daysLeft} days";
                }

                return $emp;
            })
            ->sortBy(fn($emp) => Carbon::parse($emp->dob)->setYear(now()->year)->dayOfYear)
            ->values();
    }





    public function mount(): void
    {
        $locale = app()->getLocale();
        $titleCol = $locale === 'ar' ? 'title_ar' : 'title_en';
        $deptCol  = $locale === 'ar' ? 'name_ar' : 'name_en';

        $user = auth()->user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }

        $this->employee = $user->profile()->with(['media', 'city'])->first();

        if ($this->employee) {
            $this->contract = Contract::with(['position.department'])
                ->where('employee_id', $this->employee->id)
                ->orderByDesc('start_date')
                ->first();

            $this->positionTitle  = $this->contract?->position?->$titleCol ?? '—';
            $this->departmentName = $this->contract?->position?->department?->$deptCol ?? '—';
            $this->joinedOn = $this->contract?->start_date
                ? date('d M Y', strtotime($this->contract->start_date))
                : '—';
            $this->workEmail = $user->email ?? '—';
        } else {
            $this->positionTitle = $this->departmentName = $this->joinedOn = $this->workEmail = '—';
        }
    }

    public function render()
    {
        return view('livewire.dashboards.employee-dashboard', [
            'employee'          => $this->employee,
            'contract'          => $this->contract,
            'positionTitle'     => $this->positionTitle,
            'departmentName'    => $this->departmentName,
            'joinedOn'          => $this->joinedOn,
            'workEmail'         => $this->workEmail,
            'todayBirthdays'    => $this->todayBirthdays,
            'upcomingBirthdays' => $this->upcomingBirthdays,
        ]);
    }
}
