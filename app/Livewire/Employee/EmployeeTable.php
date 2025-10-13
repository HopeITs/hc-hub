<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\BooleanColumn;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class EmployeeTable extends LivewireTable
{
    protected string $model = Employee::class;

    protected $listeners = ['delete', 'toggleItemStatusConfirmed'];

    protected function query(): Builder
    {
        return $this->model()::query()
            ->with(['directManager', 'techManager', 'account', 'media']);
    }

    protected function globalSearchColumns(): array
    {
        return ['name', 'actual_dob'];
    }

    protected function managerOptions(): array
    {
        return Employee::query()
            ->select(['id', 'name_en', 'name_ar'])
            ->orderBy('name_'.app()->getLocale(), 'asc')
            ->get()
            ->mapWithKeys(fn ($e) => [
                $e->id => $e->name
            ])
            ->all();
    }

    protected function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),

            Column::make(trans('general.name'), 'name')
                ->displayUsing(fn ($v, Model $m) => $m->name) // uses accessor
                ->sortable(),

            Column::make(trans('general.photo'), function (Model $m): string {
                $src = $m->getFirstMediaUrl('avatar'); // use 'thumb' conversion if you have it
                return $src
                    ? '<img src="'.e($src).'" alt="'.e($m->name).'" class="size-12 border border-1 border-primary rounded-full object-cover" />'
                    : '<div class="h-12 w-12 rounded-full bg-gray-200"></div>';
            })->asHtml(),

            Column::make(trans('general.years-of-service'), 'years_of_service')->sortable(),

            SelectColumn::make(trans('general.direct_manager'), 'direct_manager_id')
                ->displayUsing(fn ($v, Model $m) =>
                $m->latestActiveContract()?->position->directManager?->employee()?->name ?? '—'

                )
                ->options($this->managerOptions())
                ->sortable()->searchable(),

            SelectColumn::make(trans('general.tech_manager'), 'tech_manager_id')
                ->displayUsing(fn ($v, Model $m) =>
                    $m->latestActiveContract()?->position->techManager?->employee()?->name ?? '—'
                )
                ->options($this->managerOptions())
                ->sortable()->searchable(),

            Column::make(trans('general.position'), 'contracts.position.title')
                ->sortable()
               ,

            Column::make(trans('general.work_email'))
                ->displayUsing(fn ($v, Model $m) => $m->account?->email ?? '—'),

            BooleanColumn::make(trans('general.is-active'), 'is_active'),

            Column::make(__('Actions'), function (Model $model): string {
                $isActive = $model->is_active ? 1 : 0;

                $toggleBtn = Blade::render(
                    '<x-forms.toggle-input class="!me-2 text-xl p-2 mb-3" active="'.$isActive.
                    '" :model-id="'.$model->getKey().
                    '" wire-event="confirmToggleItemStatus" />'
                );

                $editBtn = Blade::render(
                    '<x-table.edit-btn href="'.route('employees.edit', $model->getKey()).'" />'
                );

                $deleteBtn = Blade::render(
                    '<x-table.delete-btn :modelId="'.$model->getKey().'" />'
                );

                return $toggleBtn.$editBtn.$deleteBtn;
            })->asHtml(),
        ];
    }

    protected function filters(): array
    {
        $managerOptions = $this->managerOptions();

        return [
            SelectFilter::make(trans('general.direct_manager'), 'direct_manager_id')
                ->options($managerOptions),

            SelectFilter::make(trans('general.tech_manager'), 'tech_manager_id')
                ->options($managerOptions),

            SelectFilter::make(trans('general.status'), 'is_active')
                ->options([
                    1 => trans('general.active'),
                    0 => trans('general.inactive'),
                ]),
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
        Employee::find($payload['id'])?->delete();

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
        $item = Employee::findOrFail($payload['id']);
        $item->is_active = ! $item->is_active;
        $item->save();

        LivewireAlert::title(__('general.item-updated'))
            ->success()->toast()->position('top-start')->show();
    }
}
