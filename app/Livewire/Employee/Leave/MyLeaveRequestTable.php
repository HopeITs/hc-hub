<?php

namespace App\Livewire\Employee\Leave;

use App\Enums\LeaveRequestStatus;
use App\Livewire\Forms\LeaveRequestForm;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\BooleanColumn;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\DateColumn;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class MyLeaveRequestTable extends LivewireTable
{
    protected string $model = LeaveRequest::class;

    public LeaveRequestForm $form;

    public string $statusFilter = 'all';

    public $showModal = false;

    public $modalTitle = 'Modal title';

    protected $listeners = ['filterStatus' => 'setStatusFilter', 'leaveRequestSaved' => '$refresh'];

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    protected function query(): Builder
    {
        $user = auth()->user();
        $query = LeaveRequest::query()
            ->with(['leaveBalance.contract.employee', 'leaveBalance.contract.position'])
            ->orderBy('created_at', 'desc');

        $query->whereHas('leaveBalance.contract', function ($q) use ($user) {
            $q->where('employee_id', $user->profile->id);
        });

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query;
    }

    protected function columns(): array
    {
        $user = auth()->user();

        return [

            SelectColumn::make(trans_choice('general.leave-type', 1), 'leaveBalance.leaveType.'.transColumn('name'))
                ->options(LeaveType::query()->pluck(transColumn('name'), transColumn('name'))->toArray())
                ->sortable()
                ->searchable(),
            SelectColumn::make(trans_choice('general.type', 1), 'type')
                ->options(['hour' => trans_choice('general.hour', 1), 'day' => trans_choice('general.day', 1)])
                ->displayUsing(fn ($v, Model $m) => trans_choice('general.'.$m->type, 1))
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
                    if (! $value) {
                        return '-';
                    }

                    return $model->type === 'hour' ? $model->start_at->format('H:i d-m-Y') : $model->start_at->format('d-m-Y');
                })
                ->sortable(),
            DateColumn::make(__('general.end-date'), 'end_at')
                ->format('d-m-Y H:i') // keep it; used for default/export/sort context
                ->displayUsing(function (mixed $value, Model $model): string {
                    if (! $value) {
                        return '-';
                    }

                    return $model->type === 'hour' ? $model->end_at->format('H:i d-m-Y') : $model->end_at->format('d-m-Y');
                })
                ->sortable(),
            BooleanColumn::make(__('general.seen-by-hr'), 'hr_approved'),
            Column::make(__('general.actions'), function (Model $model): string {

                if ($model->status === LeaveRequestStatus::Pending) {
                    $editBtn = Blade::render('<x-table.edit-btn wire:click="$dispatch(\'openRequestLeaveEdit\', { id: '.$model->getKey().' })" />');
                    $deleteBtn = Blade::render(
                        '<x-table.delete-btn :modelId="'.$model->getKey().'" />'
                    );

                    return $editBtn.$deleteBtn;
                }

                return '';

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

    public function delete($payload): void
    {
        // Only the owner may delete, and only while still pending — deleting
        // an accepted request would leave the balance deducted.
        $leaveRequest = LeaveRequest::query()
            ->whereKey($payload['id'])
            ->pending()
            ->whereHas('leaveBalance.contract', function (Builder $q) {
                $q->where('employee_id', auth()->user()->profile?->id);
            })
            ->first();

        abort_unless($leaveRequest !== null, 403);

        $leaveRequest->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();
    }
}
