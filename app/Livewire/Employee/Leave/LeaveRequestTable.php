<?php

namespace App\Livewire\Employee\Leave;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class LeaveRequestTable extends LivewireTable
{
    protected string $model = LeaveRequest::class;

    protected function query(): Builder
    {
        return $this->model()::query()->with('leaveBalance.contract.employee')->orderBy('created_at', 'desc');
    }
    protected function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),
            SelectColumn::make(trans_choice('general.leave-type',1), 'leaveBalance.leaveType.' . transColumn('name'))
                ->options(LeaveType::query()->get()->pluck(transColumn('name'), transColumn('name'))->toArray())
                ->sortable()->searchable(),
            Column::make('Start date', 'start_date')->sortable(),
            Column::make('End date', 'end_date')->sortable(),


        ];
    }



}
