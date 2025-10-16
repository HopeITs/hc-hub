<?php

namespace App\Livewire\Employee\Leave;

use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class LeaveRequestTable extends LivewireTable
{
    protected string $model = LeaveRequest::class;

    protected function query(): Builder
    {
        return $this->model()::query()->with('leaveBalance.contract.employee');
    }
    protected function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),


        ];
    }



}
