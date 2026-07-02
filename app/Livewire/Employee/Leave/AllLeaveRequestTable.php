<?php

namespace App\Livewire\Employee\Leave;

use App\Enums\LeaveRequestStatus;
use App\Livewire\Forms\LeaveRequestForm;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Notifications\HRSawLeaveRequest;
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

    protected $listeners = ['filterStatus' => 'setStatusFilter', 'toggleItemStatusConfirmed'];

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    protected function query(): Builder
    {
        $query = LeaveRequest::query()
            ->visibleTo(auth()->user())
            ->with(['leaveBalance.contract.employee', 'leaveBalance.contract.position'])
            ->orderBy('created_at', 'desc');

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
            SelectColumn::make(trans_choice('general.employee', 1), 'leaveBalance.contract.employee_id')
                ->options(
                    Employee::query()
                        ->select(['id', 'first_name_en', 'first_name_ar', 'last_name_en', 'last_name_ar'])
                        ->orderBy(transColumn('first_name'))
                        ->get()
                        ->mapWithKeys(fn ($e) => [$e->id => $e->name])
                        ->toArray()
                )
                ->displayUsing(fn ($v, Model $m) => $m->leaveBalance?->contract?->employee?->name ?? '—')
                ->sortable()
                ->searchable()
                ->visible(str_starts_with($user->myRole(), 'HR-') || $user->myRole() === 'manager'),
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
            DateColumn::make(__('general.request_date'), 'created_at')
                ->format('d-m-Y H:i') // keep it; used for default/export/sort context
                ->displayUsing(function (mixed $value, Model $model): string {
                    if (! $value) {
                        return '-';
                    }

                    return $model->created_at->format('d-m-Y');
                })
                ->sortable(),

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
            Column::make(__('general.seen-by-hr'), function (Model $model): string {
                if ($model->status !== LeaveRequestStatus::Pending) {
                    $isActive = $model->hr_approved ? 1 : 0;

                    $toggleBtn = Blade::render(
                        '<x-forms.toggle-input class="!me-2 text-xl p-2 mb-3" active="'.$isActive.
                            '" :model-id="'.$model->getKey().
                            '" wire-event="confirmToggleItemStatus" />'
                    );

                    return $toggleBtn;
                }

                return '';
            })->visible(str_contains($user->myRole(), 'HR-'))->asHtml(),
            Column::make(__('general.actions'), function (Model $model): string {

                $decisionBtn = '';
                $editBtn = '';

                if ($model->status === LeaveRequestStatus::Pending) {
                    $decisionBtn = Blade::render('<x-table.decision-btn wire:click="$dispatch(\'openDecisionRequestLeave\', { id: '.$model->getKey().' })" />');
                }
                if ($model->status !== LeaveRequestStatus::Pending) {
                    $editBtn = Blade::render('<x-table.edit-btn wire:click="$dispatch(\'openRequestLeaveEdit\', { id: '.$model->getKey().' })" />');
                }

                $showBtn = Blade::render('<x-table.show-btn wire:click="$dispatch(\'openShowRequestLeave\', { id: '.$model->getKey().' })" />');

                return $decisionBtn.$editBtn.$showBtn;
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
        // Resolve through the visibility scope so HR/managers can only delete
        // requests they are allowed to see.
        LeaveRequest::query()
            ->visibleTo(auth()->user())
            ->findOrFail($payload['id'])
            ->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();
    }

    public function confirmToggleItemStatus($id): void
    {
        LivewireAlert::title(__('general.toggle-status'))
            ->text(__('general.confirm-status-change'))
            ->question()
            ->withConfirmButton(__('general.save'))
            ->onConfirm('toggleItemStatusConfirmed', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function toggleItemStatusConfirmed($payload): void
    {
        // The "seen by HR" flag is an HR-only control (the column itself is
        // only rendered for HR roles).
        abort_unless(auth()->user()->activeRoleIs('HR', 'super-admin'), 403);

        $item = LeaveRequest::query()
            ->visibleTo(auth()->user())
            ->findOrFail($payload['id']);
        $item->hr_approved = ! $item->hr_approved;
        $item->save();

        // Notify
        $leaveRequest = $item->load('leaveBalance.contract.position');
        $directManage = $leaveRequest->leaveBalance->contract->position->directManager?->employee()->account;
        $techManager = $leaveRequest->leaveBalance->contract->position->techManager?->employee()->account;
        $employee = $leaveRequest->leaveBalance->contract->employee->account;

        isset($directManage) ? $directManage->notify(new HRSawLeaveRequest($leaveRequest, 'manager')) : '';
        isset($techManager) ? $techManager->notify(new HRSawLeaveRequest($leaveRequest, 'manager')) : '';
        isset($employee) ? $employee->notify(new HRSawLeaveRequest($leaveRequest, 'employee')) : '';
        $this->dispatch('notification-created');

        LivewireAlert::title(__('general.item-updated'))
            ->success()->toast()->position('top-start')->show();
    }
}
