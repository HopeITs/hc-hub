<?php

namespace App\Livewire\Basics;

use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\BooleanColumn;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class DepartmentTable extends LivewireTable
{
    protected string $model = Department::class;
    public $itemID;

    protected function query(): \Illuminate\Database\Eloquent\Builder
    {
        return Department::query();
    }

    protected function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),

            Column::make(__('general.department-name'), 'name')
                ->sortable()
                ->searchable(),

            Column::make(__('general.code'), 'code')
                ->sortable()
                ->searchable(),

            Column::make(__('general.department-description'), function (Model $m) {
                return $m->{'description_' . app()->getLocale()} ?: '-';
            })->asHtml(),

            Column::make(__('general.department-email'), 'email')
                ->sortable()
                ->searchable(),

            BooleanColumn::make(__('general.status'), 'is_active'),

            Column::make(__('general.actions'), function (Model $m): string {
                return Blade::render(
                    '<x-forms.toggle-input active="' . ($m->is_active ? 1 : 0) . '" :model-id="' . $m->id . '" wire-event="confirmToggleItemStatus" />'
                );
            })->asHtml(),
        ];
    }

    public function confirmToggleItemStatus($id)
    {
        $this->itemID = $id;

        LivewireAlert::title(__('general.toggle-status'))
            ->text(__('general.confirm-status-change'))
            ->question()
            ->withConfirmButton(__('general.save'))
            ->onConfirm('toggleItemStatusConfirmed', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function toggleItemStatusConfirmed($payload)
    {
        $item = Department::findOrFail($payload['id']);
        $item->is_active = ! $item->is_active;
        $item->save();

        LivewireAlert::title(__('general.item-updated'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }
}
