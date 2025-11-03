<?php

namespace App\Livewire\Employee\Leave;

use App\Enums\LeaveRequestStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\DateColumn;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class LeaveRequestTable extends LivewireTable
{
    protected string $model = LeaveRequest::class;

    public string $statusFilter = 'all';

    protected $listeners = ['filterStatus' => 'setStatusFilter'];

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    protected function query(): Builder
    {
        $user = auth()->user();
        $role = $user->activeRole()?->name; // assuming activeRole() returns a Role model
        $query = LeaveRequest::query()
            ->with('leaveBalance.contract.employee')
            ->orderBy('created_at', 'desc');

        if ($role) {
            if ($role !== 'HR-SYR' &&  str_starts_with($role, 'HR-')) {

                // Extract city code from role, e.g. HR-ALP → ALP
                $cityCode = substr($role, 3);

                // Join or filter based on the employee’s city code
                $query->whereHas('leaveBalance.contract.employee.city', function ($q) use ($cityCode) {
                    $q->where('slug', $cityCode);
                });
            } else {
                // Not HR – show only own requests
                $query->whereHas('leaveBalance.contract', function ($q) use ($user) {
                    $q->where('employee_id', $user->profile->id);
                });
            }
        } else {
            // Fallback: not HR, no active role
            $query->whereHas('leaveBalance.contract', function ($q) use ($user) {
                $q->where('employee_id', $user->profile->id);
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query;
    }


    protected function columns(): array
    {
        $user = auth()->user();
        return [


            SelectColumn::make(trans_choice('general.leave-type', 1), 'leaveBalance.leaveType.' . transColumn('name'))
                ->options(LeaveType::query()->pluck(transColumn('name'), transColumn('name'))->toArray())
                ->sortable()
                ->searchable(),
            SelectColumn::make(trans_choice('general.employee', 1), 'leaveBalance.contract.employee.' . transColumn('name'))
                ->options(Employee::query()->pluck(transColumn('name'), transColumn('name'))->toArray())
                ->sortable()
                ->searchable()->visible(str_starts_with($user->activeRole()?->name, 'HR-')),

            Column::make(__('general.status'), function (mixed $value, Model $model): string {
                $status = LeaveRequestStatus::getLocalizedName($value->status);
                return "<span class='px-2 py-1 text-xs font-semibold rounded-md bg-{$status['color']}-100 text-{$status['color']}-800'>
                    {$status['value']}
                </span>";
            })->asHtml(),

            DateColumn::make(__('general.start-date'), 'start_at')->format('d-m-Y')->sortable(),
            DateColumn::make(__('general.end-date'), 'end_at')->format('d-m-Y')->sortable(),
        ];
    }
}
