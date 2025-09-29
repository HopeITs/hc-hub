<?php

namespace App\Livewire\ProgramManagement;

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
        $loc = app()->getLocale();
        return Employee::query()->orderBy("name->$loc")->get(['id', 'name'])
            ->mapWithKeys(fn($e) => [$e->id => $e->getTranslation('name', $loc)])->all();
    }

    protected function cityOptions(): array
    {
        $loc = app()->getLocale();
        return City::query()->whereHas('centers')->orderBy("name->$loc")->get(['id', 'name'])
            ->mapWithKeys(fn($c) => [$c->id => $c->getTranslation('name', $loc)])->all();
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

            SelectColumn::make(__('general.employee'), 'employee_id')
                ->displayUsing(fn($v, Model $m) => $m->employee?->getTranslation('name', $loc) ?? '—')
                ->options($this->employeeOptions())
                ->sortable(),

            SelectColumn::make(trans_choice('general.city', 1), 'city_id')
                ->displayUsing(fn($v, Model $m) => $m->city?->getTranslation('name', $loc) ?? '—')
                ->options($this->cityOptions())
                ->sortable(),

            SelectColumn::make(__('general.item'), 'item_id')
                ->displayUsing(fn($v, Model $m) => $m->item?->name ?? '—')
                ->options($this->itemOptions())
                ->sortable(),

            Column::make(__('Actions'), function (Model $m): string {
                $id = $m->getKey();

                $editBtn = Blade::render(
                    '<x-table.edit-btn wire:click="$dispatchTo(\'program-management.default-deliverables-module\', \'openDefaultDeliverableEdit\', { id: ' . $id . ' })" />'
                );

                $deleteBtn = Blade::render(
                    '<x-table.delete-btn wire:click="confirmDelete(' . $id . ')" :modelId="' . $id . '" />'
                );

                return '<div class="flex items-center justify-center gap-2">' . $editBtn . $deleteBtn . '</div>';
            })->asHtml(),
        ];
    }

    protected function filters(): array
    {
        return [
            SelectFilter::make(__('general.employee'), 'employee_id')->options($this->employeeOptions()),
            SelectFilter::make(trans_choice('general.city', 1), 'city_id')->options($this->cityOptions()),
            SelectFilter::make(__('general.item'), 'item_id')->options($this->itemOptions()),
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
