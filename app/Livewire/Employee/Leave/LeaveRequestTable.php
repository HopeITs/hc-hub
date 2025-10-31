<?php

namespace App\Livewire\Employee\Leave;

use App\Enums\LeaveRequestStatus;
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
        $query = $this->model()::query()
            ->with('leaveBalance.contract.employee')
            ->orderBy('created_at', 'desc');

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query;
    }

    protected function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),

            SelectColumn::make(trans_choice('general.leave-type', 1), 'leaveBalance.leaveType.' . transColumn('name'))
                ->options(LeaveType::query()->pluck(transColumn('name'), transColumn('name'))->toArray())
                ->sortable()
                ->searchable(),

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
