<?php

namespace App\Livewire\Employee\Leave;

use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveTypeCases;
use App\Livewire\Forms\LeaveBalanceForm;
use App\Livewire\Forms\LeaveRequestForm;
use App\Models\Employee;
use App\Models\LeaveBalance;
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

class EmpLeaveBalanceTable extends LivewireTable
{
    protected string $model = Employee::class;
    public LeaveBalanceForm $form;

    public $showModal = false;
    public $modalTitle = 'Modal title';


    protected function query(): Builder
    {
        $user = auth()->user();
        $role = $user->myRole();

        $query = Employee::query()
            ->whereHas('contracts.leaveBalances' , function ($query) {
                return $query->where('type_id', LeaveTypeCases::Annual);
            });

        if ($role === 'HR-SYR') {
            return $query;
        } else {

            $cityCode = substr($role, 3);
            $query->whereHas('city', function ($qq) use ($cityCode) {
                $qq->where('slug', $cityCode);

            });

        }


        return $query;
    }


    protected function columns(): array
    {
        $user = auth()->user();
        return [
            Column::make(trans('general.name'), transColumn('name'))
                ->displayUsing(fn($v, Model $m) => $m->name) // uses accessor
                ->sortable()->searchable(),
            Column::make(trans('general.years-of-service'), 'years_of_service_formatted')->computed()->sortable(),
            Column::make(__('general.deserved'), function (mixed $value, Model $model) {
                return $model->latestActiveContract()?->leaveBalances()->where('type_id', LeaveTypeCases::Annual)->first()?->deserved_formatted;
            })->asHtml(),
            Column::make(__('general.used'), function (mixed $value, Model $model) {
                return $model->latestActiveContract()?->leaveBalances()->where('type_id', LeaveTypeCases::Annual)->first()?->used_formatted;
            })->asHtml(),
            Column::make(__('general.remaining'), function (mixed $value, Model $model) {
                return $model->latestActiveContract()?->leaveBalances()->where('type_id', LeaveTypeCases::Annual)->first()?->remaining;
            })->asHtml(),

            Column::make(__('general.actions'), function (Model $model): string {
                $editBtn = '';
                if (isset($model->latestActiveContract()?->leaveBalances()->where('type_id', LeaveTypeCases::Annual)->first()->id)) {
                    $editBtn = Blade::render('<x-table.edit-btn wire:click="$dispatch(\'openEdit\', { id: ' . $model->latestActiveContract()?->leaveBalances()->where('type_id', LeaveTypeCases::Annual)->first()->id . ' })" />');
                }


                return $editBtn;
            })->asHtml(),

        ];
    }


    public function edit($id)
    {
        $model = LeaveBalance::findOrFail($id);

        $employee = auth()->user()->profile;
        $modalTitle = __('general.edit');
        $form = $this->form;

        $this->showModal = true;
    }


}
