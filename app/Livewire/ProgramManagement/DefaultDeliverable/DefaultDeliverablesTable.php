<?php

namespace App\Livewire\ProgramManagement\DefaultDeliverable;

use App\Models\ChecklistItem;
use App\Models\City;
use App\Models\DefaultDeliverable as DefaultDeliverableModel;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class DefaultDeliverablesTable extends LivewireTable
{
    protected string $model = DefaultDeliverableModel::class;

    protected $listeners = [
        'delete',
        'refreshDefaultDeliverablesTable' => '$refresh',
    ];

    protected function query(): Builder
    {
        return $this->model()::query()->with(['employee', 'city', 'item']);
    }

    protected function globalSearchColumns(): array
    {
        return ['id'];
    }

    protected function employeeOptions(): array
    {
        return Employee::query()->whereHas('contracts',function ($query){
            $query->whereHas('position',function ($subQuery){
                $subQuery->whereHas('department',function($q){
                    $q->whereIn('code' , ['IT', 'CT', 'HR']);
                } );
            });
        })->orderBy(transColumn('name'))->get(['id', transColumn('name')])
            ->mapWithKeys(fn($e) => [$e->id => $e->name])->all();
    }

    protected function cityOptions(): array
    {
        $loc = app()->getLocale();
        return City::query()->whereHas('centers')->get(['id', transColumn('name')])
            ->mapWithKeys(fn($c) => [$c->id => $c->name])->all();
    }

    protected function itemOptions(): array
    {
        return ChecklistItem::query()->orderBy('order')->pluck('name', 'id')->toArray();
    }

    protected function columns(): array
    {
        $loc = app()->getLocale();

        return [
            Column::make('ID', 'id')->sortable(),

            SelectColumn::make(trans_choice('general.employee',1), 'employee.name')
                ->displayUsing(fn($v, Model $m) => $m->employee?->name ?? '—')
                ->options($this->employeeOptions())
                ->sortable()->searchable(),

            SelectColumn::make(trans_choice('general.city', 1), 'city_id')
                ->displayUsing(fn($v, Model $m) => $m->city?->name ?? '—')
                ->options($this->cityOptions())
                ->sortable()->searchable(),

            SelectColumn::make(__('general.item'), 'item_id')
                ->displayUsing(fn($v, Model $m) => $m->item?->name ?? '—')
                ->options($this->itemOptions())
                ->sortable()->searchable(),

            Column::make(__('general.actions'), function (Model $m): string {
                $id = $m->getKey();

                $editBtn = Blade::render(
                    '<x-table.edit-btn wire:click="$dispatch(\'openDefaultDeliverableEdit\', { id: ' . $id . ' })" />'
                );

                $deleteBtn = Blade::render(
                    '<x-table.delete-btn wire:click="confirmDelete(' . $id . ')" :modelId="' . $id . '" />'
                );

                return '<div class="flex items-center justify-center gap-2">' . $editBtn . $deleteBtn . '</div>';
            })->asHtml(),
        ];
    }

    public function confirmDelete(int $id): void
    {
        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('delete', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function delete(array $payload): void
    {
        DefaultDeliverableModel::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();

        $this->dispatch('refreshDefaultDeliverablesTable');
    }
}
