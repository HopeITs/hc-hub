<?php

namespace App\Livewire\Employee\Leave;

use App\Enums\LeaveRequestStatus;
use App\Livewire\Forms\LeaveRequestForm;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\DateColumn;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class AllLeaveRequestTable extends LivewireTable
{
    protected string $model = LeaveRequest::class;
    public LeaveRequestForm $form;
    public string $statusFilter = 'all';
    public $showModal = false;
    public $modalTitle = 'Modal title';

    protected $listeners = ['filterStatus' => 'setStatusFilter'];

    public $modalBody = '';

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
            ->with(['leaveBalance.contract.employee', 'leaveBalance.contract.position'])
            ->orderBy('created_at', 'desc');

        if ($role) {
            if ($role !== 'HR-SYR' && str_starts_with($role, 'HR-')) {

                // Extract city code from role, e.g. HR-ALP → ALP
                $cityCode = substr($role, 3);

                // Join or filter based on the employee’s city code
                $query->whereHas('leaveBalance.contract.employee.city', function ($q) use ($cityCode) {
                    $q->where('slug', $cityCode);
                });
            } elseif ($role === 'Manager') {

                $query->whereHas('leaveBalance.contract.position.', function ($q) use ($user) {
                    $q->where('direct_manager_id', $user->profile->id);
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
                ->searchable()->visible(str_starts_with($user->activeRole()?->name, 'HR-') || $user->activeRole()?->name === 'Manager'),
            SelectColumn::make(trans_choice('general.type', 1), 'type')
                ->options(['hour' => trans_choice('general.hour', 1), 'day' => trans_choice('general.day', 1)])
                ->displayUsing(fn($v, Model $m) => trans_choice('general.' . $m->type, 1))
                ->sortable()
                ->searchable(),

            Column::make(__('general.duration'), 'duration')->computed(),
            Column::make(__('general.status'), function (mixed $value, Model $model): string {
                $status = LeaveRequestStatus::getLocalizedName($value->status);
                return "<span class='px-2 py-1 text-xs font-semibold rounded-md bg-{$status['color']}-100 text-{$status['color']}-800'>
                    {$status['value']}
                </span>";
            })->asHtml(),


            DateColumn::make(__('general.start-date'), 'start_at')
                ->format('d-m-Y H:i') // keep it; used for default/export/sort context
                ->displayUsing(function (mixed $value, Model $model): string {
                    if (!$value) return '-';
                    return $model->type === 'hour' ? $model->start_at->format('H:i d-m-Y') : $model->start_at->format('d-m-Y');
                })
                ->sortable(),
            DateColumn::make(__('general.end-date'), 'end_at')
                ->format('d-m-Y H:i') // keep it; used for default/export/sort context
                ->displayUsing(function (mixed $value, Model $model): string {
                    if (!$value) return '-';
                    return $model->type === 'hour' ? $model->end_at->format('H:i d-m-Y') : $model->end_at->format('d-m-Y');
                })
                ->sortable(),

            Column::make(__('Actions'), function (Model $model): string {
                $isActive = $model->is_active ? 1 : 0;

                $toggleBtn = Blade::render(
                    '<x-forms.toggle-input class="!me-2 text-xl p-2 mb-3" active="' . $isActive .
                    '" :model-id="' . $model->getKey() .
                    '" wire-event="confirmToggleItemStatus" />'
                );

                $editBtn = Blade::render(
                    '<x-table.edit-btn wire:click="edit(' . $model->getKey() . ')" />'
                );

                $deleteBtn = Blade::render(
                    '<x-table.delete-btn :modelId="' . $model->getKey() . '" />'
                );

                return $toggleBtn . $editBtn . $deleteBtn;
            })->asHtml(),

        ];
    }

    public function confirmDelete($id): void
    {
        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('delete', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function edit($id)
    {
        $model = LeaveRequest::findOrFail($id);


        $this->form->setLeaveRequest($model);
        $employee = auth()->user()->profile;
        $modalTitle = __('general.edit');
        $form = $this->form;
        $availableLeaves = auth()->user()->profile->availableLeaves();
        $allAvailableLeaves = $employee->allAvailableLeaves();
        $alternativeEmployees = Employee::where('city_id', $employee->city_id)->where('id', '!=', $employee->id)
            ->whereHas('contracts', function ($query) use ($employee) {
                $query->whereHas('position.department', function ($query) use ($employee) {
                    $query->where('id', $employee->department->id ?? null);
                });
            })->get();
        $this->modalBody = Blade::render('livewire.employee.leave.form',compact('modalTitle','form','allAvailableLeaves','alternativeEmployees','availableLeaves'));
        $this->showModal = true;
    }


    public function delete($payload): void
    {
        LeaveRequest::find($payload['id'])?->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();
    }

}
